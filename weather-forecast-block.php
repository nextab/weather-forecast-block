<?php
/**
 * Plugin Name: Wettervorhersage-Block
 * Description: Fügt einen Wettervorhersage-Block zum Gutenberg-Editor hinzu, der die OpenWeatherMap-API verwendet
 * Version: 1.6.1
 * Author: CEATE
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: weather-forecast-block
 */

// Verhindern direkten Zugriffs
if (!defined('ABSPATH')) {
    exit;
}

// Check for required constant
if (!defined('OPENWEATHERMAP_API_KEY')) {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Bitte definieren Sie OPENWEATHERMAP_API_KEY in Ihrer wp-config.php', 'weather-forecast-block'); ?></p>
        </div>
        <?php
    });
    return;
}

// Einbinden der Block-Editor-Assets
function weather_block_register_block()
{
    if (!function_exists('register_block_type')) {
        return;
    }

    wp_register_script(
        'weather-block-editor',
        plugins_url('js/weather-block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
        filemtime(plugin_dir_path(__FILE__) . 'js/weather-block.js')
    );

    register_block_type('weather-forecast/block', array(
        'editor_script' => 'weather-block-editor',
        'render_callback' => 'weather_block_render_callback',
        'attributes' => array(
            'location' => array(
                'type' => 'string',
                'default' => 'London'
            ),
            'iconColor' => array(
                'type' => 'string',
                'default' => '#000000'
            )
        )
    ));
}
add_action('init', 'weather_block_register_block');

// Wettericons-Zuordnung
function get_weather_icon($icon_name)
{
    $icon_name_stripped = preg_replace('/\D/', '', $icon_name);
    $icon_path = plugin_dir_path(__FILE__) . 'icons/' . $icon_name_stripped . '.svg';

    if (file_exists($icon_path)) {
        $svg_content = file_get_contents($icon_path);
        // URL encode the SVG content for proper use in data URI
        $encoded_svg = rawurlencode($svg_content);
        return '<div class="weather-icon" style="mask-image: url(\'data:image/svg+xml;charset=utf-8,' . $encoded_svg . '\');"></div>';
    }
    return '';
}

// Temperatur-Icons
function get_temp_icon($type)
{
    $max_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5.81 10.124" class="temperature-max__icon"><path d="M3.401 9h-1V0h1z"></path><path d="M2.901 10.124l-2.9-3.873.8-.6 2.1 2.806L5.013 5.65l.8.6z"></path></svg>';
    $min_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5.81 10.124" class="temperature-min__icon"><path d="M3.401 9h-1V0h1z"></path><path d="M2.901 10.124l-2.9-3.873.8-.6 2.1 2.806L5.013 5.65l.8.6z"></path></svg>';
    return $type === 'max' ? $max_icon : $min_icon;
}

// Serverseitiger Render-Callback
function weather_block_render_callback($attributes)
{
    if (!current_user_can('edit_posts')) {
        return '';
    }

    $location = sanitize_text_field($attributes['location']);
    $icon_color = sanitize_hex_color($attributes['iconColor'] ?? '#000000');

    $cache_key = 'weather_' . md5($location);
    $weather_data = get_transient($cache_key);

    // Für Debugging: Cache temporär ignorieren
    // $weather_data = false; // Erzwingt einen neuen API-Aufruf

    if (false === $weather_data) {
        $weather_url = is_numeric($location)
            ? "https://api.openweathermap.org/data/2.5/forecast?id={$location}&appid=" . OPENWEATHERMAP_API_KEY . "&units=metric&lang=de"
            : "https://api.openweathermap.org/data/2.5/forecast?q=" . urlencode($location) . "&appid=" . OPENWEATHERMAP_API_KEY . "&units=metric&lang=de";

        $weather_response = wp_remote_get($weather_url);

        if (is_wp_error($weather_response)) {
            return '<p>' . esc_html__('Fehler beim Abrufen der Wetterdaten: ', 'weather-forecast-block') . esc_html($weather_response->get_error_message()) . '</p>';
        }

        $weather_data = json_decode(wp_remote_retrieve_body($weather_response));

        // Debugging: Rohdaten ausgeben, um die API-Antwort zu überprüfen
        // if (current_user_can('administrator')) {
        //     echo '<pre>API-URL: ' . esc_html($weather_url) . '</pre>';
        //     echo '<pre>API-Daten: ' . print_r($weather_data, true) . '</pre>';
        // }

        if (isset($weather_data->cod) && $weather_data->cod == 401) {
            return '<p>' . esc_html__('Ungültiger API-Schlüssel. Bitte überprüfen Sie Ihre Konfiguration.', 'weather-forecast-block') . '</p>';
        }
        if (isset($weather_data->cod) && $weather_data->cod != 200) {
            return '<p>' . esc_html__('Fehler beim Abrufen der Wetterdaten: ', 'weather-forecast-block') . esc_html($weather_data->message) . '</p>';
        }
        set_transient($cache_key, $weather_data, HOUR_IN_SECONDS);
    }

    if (empty($weather_data)) {
        return '<p>' . esc_html__('Keine Wetterdaten verfügbar', 'weather-forecast-block') . '</p>';
    }

    // Define "today" based on current server time
    $today = date('Y-m-d');
    $current_weather = $weather_data->list[0]; // Use earliest forecast as current approximation
    $daily_forecasts = array();
    $temps = array();
    $pops = array();
    $dominant_forecast = array();

    foreach ($weather_data->list as $forecast) {
        $date = date('Y-m-d', $forecast->dt);
        // Skip today's data for forecast section if present
        if ($date === $today) {
            continue;
        }
        if (!isset($daily_forecasts[$date])) {
            $daily_forecasts[$date] = $forecast;
            $temps[$date] = array('max' => $forecast->main->temp, 'min' => $forecast->main->temp);
            $pops[$date] = $forecast->pop;
            $dominant_forecast[$date] = $forecast;
        } else {
            $temps[$date]['max'] = max($temps[$date]['max'], $forecast->main->temp);
            $temps[$date]['min'] = min($temps[$date]['min'], $forecast->main->temp);
            $pops[$date] = max($pops[$date], $forecast->pop);
            if ($forecast->pop > $dominant_forecast[$date]->pop) {
                $dominant_forecast[$date] = $forecast;
            }
        }
        // Limit to 5 future days (API gives 5 days total, we use 1 as current)
        if (count($daily_forecasts) >= 5) {
            break;
        }
    }

    $output = '<div class="weather-forecast" style="--weather-icon-color: ' . esc_attr($icon_color) . '">';

    // Aktuelles Wetter (approximated from first forecast)
    $output .= '<div class="weather-day current-weather">';
    $output .= '<h2 class="weather-location">' . esc_html($location) . '</h2>';
    $output .= '<div class="current-weather-main">';
    $output .= get_weather_icon($current_weather->weather[0]->icon);
    $output .= '<div class="weather-temp-large">' . round($current_weather->main->temp) . '°</div>';
    $output .= '</div>';
    $output .= '<div class="current-weather-meta">';
    $output .= '<div>' . esc_html($current_weather->weather[0]->description) . '</div><span>•</span>';
    $output .= '<div>' . esc_html__('Gefühlt ', 'weather-forecast-block') . round($current_weather->main->feels_like) . '°</div>';
    $output .= '</div>';
    $output .= '</div>';

    // Nächste 5 Tage
    $days = array_slice($daily_forecasts, 0, 5, true); // Use all available future days
    foreach ($days as $date => $forecast) {
        $day_name = strtoupper(substr(date('D', $forecast->dt), 0, 3));
        $day_names_de = array(
            'MON' => 'MO',
            'TUE' => 'DI',
            'WED' => 'MI',
            'THU' => 'DO',
            'FRI' => 'FR',
            'SAT' => 'SA',
            'SUN' => 'SO'
        );
        $day_name = $day_names_de[$day_name] ?? $day_name;
        $day_number = date('j', $forecast->dt);

        $chosen_forecast = $dominant_forecast[$date];
        $icon = $chosen_forecast->weather[0]->icon;

        $output .= '<div class="weather-day">';
        $output .= '<div class="weather-day-header">' . '<span class="weather-day-name">' . esc_html($day_name) . '</span>' . ' ' . esc_html($day_number) . '</div>';
        $output .= '<div class="weather-main-info">';
        $output .= get_weather_icon($icon);
        $output .= '<div class="weather-temp-range">';
        $output .= get_temp_icon('max') . '<span class="temp-max">' . round($temps[$date]['max']) . '°</span>';
        $output .= get_temp_icon('min') . '<span class="temp-min">' . round($temps[$date]['min']) . '°</span>';
        $output .= '</div>';
        $output .= '<div class="weather-precipitation">' . esc_html__('Regen: ', 'weather-forecast-block') . round($pops[$date] * 100) . '%</div>';
        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}

function weather_block_styles()
{
    wp_enqueue_style(
        'weather-block-style',
        plugins_url('css/style.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'css/style.css')
    );
}
add_action('enqueue_block_assets', 'weather_block_styles');