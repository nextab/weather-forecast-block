(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, TextControl } = wp.components;
    const { useState } = wp.element;

    // Inline-CSS im Block-Editor hinzufügen
    if (wp.element.render) { // Sicherstellen, dass dies nur im Editor läuft
        const style = document.createElement('style');
        style.textContent = `
            .weather-block-editor .weather-preview {
                padding: 20px;
                background: #f5f5f5;
                text-align: center;
            }
        `;
        document.head.appendChild(style);
    }

    registerBlockType('weather-forecast/block', {
        title: 'Wettervorhersage',
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

        edit: function(props) {
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
                                { title: 'Wettereinstellungen' },
                                [
                                    wp.element.createElement(
                                        TextControl,
                                        {
                                            label: 'OpenWeatherMap API-Schlüssel',
                                            value: apiKey,
                                            onChange: (newApiKey) => {
                                                setAttributes({ apiKey: newApiKey });
                                            },
                                            help: 'Geben Sie hier Ihren OpenWeatherMap API-Schlüssel ein. Sie können einen auf openweathermap.org erhalten.'
                                        }
                                    ),
                                    wp.element.createElement(
                                        TextControl,
                                        {
                                            label: 'Ortsangabe',
                                            value: location,
                                            onChange: (newLocation) => {
                                                setAttributes({ location: newLocation });
                                            },
                                            help: 'Geben Sie einen Städtenamen oder einen OpenWeatherMap-Ortscode ein (z. B. "London", "Paris" oder eine Stadt-ID).'
                                        }
                                    )
                                ]
                            )
                        ),
                        wp.element.createElement(
                            'div',
                            { className: 'weather-preview' },
                            `Wettervorhersage für ${location} wird hier angezeigt`
                        )
                    ]
                )
            );
        },

        save: function() {
            return null;
        }
    });
})(window.wp);