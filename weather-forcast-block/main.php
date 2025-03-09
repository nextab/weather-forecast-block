<?php
/*
Plugin Name: Weather Forecast Block
Description: Adds a weather forecast block to the Gutenberg editor using OpenWeatherMap API
Version: 1.3.0
Author: CEATE
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue block editor assets
function weather_block_register_block()
{
    wp_register_script(
        'weather-block-editor',
        plugins_url('block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
        filemtime(plugin_dir_path(__FILE__) . 'block.js')
    );

    register_block_type('weather-forecast/block', array(
        'editor_script' => 'weather-block-editor',
        'render_callback' => 'weather_block_render_callback',
        'attributes' => array(
            'location' => array(
                'type' => 'string',
                'default' => 'London'
            ),
            'apiKey' => array(
                'type' => 'string',
                'default' => ''
            )
        )
    ));
}
add_action('init', 'weather_block_register_block');

// Weather icons mapping
function get_weather_icon($description)
{
    // Convert description to file name format (lowercase, spaces to dashes)
    $icon_name = str_replace(' ', '-', strtolower($description));
    $icon_path = plugin_dir_path(__FILE__) . 'icons/' . $icon_name . '.svg';
    $icon_url = plugins_url('icons/' . $icon_name . '.svg', __FILE__);

    // Check if the icon file exists
    if (file_exists($icon_path)) {
        // Load the SVG content
        $svg_content = file_get_contents($icon_path);
        // Add a class to the SVG for styling
        $svg_content = str_replace('<svg', '<svg class="weather-icon"', $svg_content);
        return $svg_content;
    }

    // Fallback to text if icon not found
    return '<span>' . esc_html($description) . '</span>';
}

// Temperature icons
function get_temp_icon($type)
{
    $max_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5.81 10.124" class="temperature-min-max__icon"><path d="M3.401 9h-1V0h1z"></path><path d="M2.901 10.124l-2.9-3.873.8-.6 2.1 2.806L5.013 5.65l.8.6z"></path></svg>';
    $min_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5.81 10.124" class="temperature-min-max__icon"><path d="M3.401 9h-1V0h1z"></path><path d="M2.901 10.124l-2.9-3.873.8-.6 2.1 2.806L5.013 5.65l.8.6z"></path></svg>';
    return $type === 'max' ? $max_icon : $min_icon;
}

// Server-side render callback
function weather_block_render_callback($attributes)
{
    $location = $attributes['location'];
    $api_key = $attributes['apiKey'];

    // Check if API key is provided
    if (empty($api_key)) {
        return '<p>Please enter your OpenWeatherMap API key in the block settings.</p>';
    }

    $cache_key = 'weather_' . md5($location);
    $weather_data = get_transient($cache_key);

    if (false === $weather_data) {
        $lat_lon_url = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($location) . "&limit=1&appid=" . $api_key;
        $lat_lon_response = wp_remote_get($lat_lon_url);

        if (is_wp_error($lat_lon_response)) {
            return '<p>Error fetching weather data: ' . esc_html($lat_lon_response->get_error_message()) . '</p>';
        }

        $lat_lon_data = json_decode(wp_remote_retrieve_body($lat_lon_response));
        if (empty($lat_lon_data)) {
            return '<p>Location not found</p>';
        }

        $lat = $lat_lon_data[0]->lat;
        $lon = $lat_lon_data[0]->lon;

        $weather_url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$api_key}&units=metric";
        $weather_response = wp_remote_get($weather_url);

        if (is_wp_error($weather_response)) {
            return '<p>Error fetching weather data: ' . esc_html($weather_response->get_error_message()) . '</p>';
        }

        $weather_data = json_decode(wp_remote_retrieve_body($weather_response));
        if (isset($weather_data->cod) && $weather_data->cod == 401) {
            return '<p>Invalid API key. Please check your OpenWeatherMap API key in the block settings.</p>';
        }
        set_transient($cache_key, $weather_data, HOUR_IN_SECONDS);
    }

    if (empty($weather_data)) {
        return '<p>No weather data available</p>';
    }

    // Current weather (first entry)
    $current_weather = $weather_data->list[0];
    $daily_forecasts = array();
    $temps = array();
    foreach ($weather_data->list as $forecast) {
        $date = date('Y-m-d', $forecast->dt);
        if (!isset($daily_forecasts[$date])) {
            $daily_forecasts[$date] = $forecast;
            $temps[$date] = array('max' => $forecast->main->temp, 'min' => $forecast->main->temp);
        } else {
            $temps[$date]['max'] = max($temps[$date]['max'], $forecast->main->temp);
            $temps[$date]['min'] = min($temps[$date]['min'], $forecast->main->temp);
        }
        if (count($daily_forecasts) >= 7)
            break;
    }

    $output = '<div class="weather-forecast">';

    // First column - Current weather
    $output .= '<div class="weather-day current-weather">';
    $output .= '<h2 class="weather-location">' . esc_html($location) . '</h2>';
    $output .= '<div class="weather-current">';
    $output .= '<div class="weather-icon-wrapper">' . get_weather_icon($current_weather->weather[0]->description) . '</div>';
    $output .= '<div class="weather-temp-large">' . round($current_weather->main->temp) . '°</div>';
    $output .= '</div>';
    $output .= '<div class="weather-current-meta">';
    $output .= '<div class="weather-description">' . esc_html($current_weather->weather[0]->description) . '</div><span>&#8226;</span>';
    $output .= '<div class="weather-feels-like">Feels like ' . round($current_weather->main->feels_like) . '°</div>';
    $output .= '</div>';
    $output .= '</div>';

    // Next 6 days
    $days = array_slice($daily_forecasts, 1, 6, true);
    foreach ($days as $date => $forecast) {
        $day_name = strtoupper(substr(date('D', $forecast->dt), 0, 3));
        $day_number = date('j', $forecast->dt);

        $output .= '<div class="weather-day">';
        $output .= '<div class="weather-day-header">' . '<span class="weather-day-name">' . $day_name . '</span>' . ' ' . $day_number . '</div>';
        $output .= '<div class="weather-icon-wrapper">' . get_weather_icon($forecast->weather[0]->description) . '</div>';
        $output .= '<div class="weather-temp-range">';
        $output .= get_temp_icon('max') . '<span class="temp-max">' . round($temps[$date]['max']) . '°</span>';
        $output .= get_temp_icon('min') . '<span class="temp-min">' . round($temps[$date]['min']) . '°</span>';
        $output .= '</div>';
        $output .= '<div class="weather-precipitation">Regen: ' . (isset($forecast->pop) ? round($forecast->pop * 100) : 0) . '%</div>';
        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}

// Basic styling
function weather_block_styles()
{
    wp_enqueue_style(
        'weather-block-style',
        plugins_url('style.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'style.css')
    );
}
add_action('enqueue_block_assets', 'weather_block_styles');