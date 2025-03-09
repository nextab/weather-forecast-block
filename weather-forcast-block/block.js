(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, TextControl } = wp.components;
    const { useState } = wp.element;

    // Predefined list of cities
    const cities = [
        { label: 'London', value: 'London' },
        { label: 'New York', value: 'New York' },
        { label: 'Tokyo', value: 'Tokyo' },
        { label: 'Paris', value: 'Paris' },
        { label: 'Sydney', value: 'Sydney' },
        { label: 'Moscow', value: 'Moscow' },
        { label: 'Berlin', value: 'Berlin' }
    ];

    registerBlockType('weather-forecast/block', {
        title: 'Weather Forecast',
        icon: 'cloud',
        category: 'widgets',
        attributes: {
            location: {
                type: 'string',
                default: 'London'
            },
            apiKey: {
                type: 'string',
                default: ''
            }
        },

        edit: function (props) {
            const { attributes, setAttributes } = props;
            const { location, apiKey } = attributes;

            return (
                wp.element.createElement(
                    'div',
                    { className: 'weather-block-editor' },
                    [
                        wp.element.createElement(
                            InspectorControls,
                            {},
                            wp.element.createElement(
                                PanelBody,
                                { title: 'Weather Settings' },
                                [
                                    wp.element.createElement(
                                        TextControl,
                                        {
                                            label: 'OpenWeatherMap API Key',
                                            value: apiKey,
                                            onChange: (newApiKey) => {
                                                setAttributes({ apiKey: newApiKey });
                                            },
                                            help: 'Enter your OpenWeatherMap API key here. You can get one from openweathermap.org'
                                        }
                                    ),
                                    wp.element.createElement(
                                        SelectControl,
                                        {
                                            label: 'Select Location',
                                            value: location,
                                            options: cities,
                                            onChange: (newLocation) => {
                                                setAttributes({ location: newLocation });
                                            }
                                        }
                                    )
                                ]
                            )
                        ),
                        wp.element.createElement(
                            'div',
                            { className: 'weather-preview' },
                            `Weather forecast for ${location} will be displayed here`
                        )
                    ]
                )
            );
        },

        save: function () {
            // Server-side rendering, so no save content needed
            return null;
        }
    });
})(window.wp);