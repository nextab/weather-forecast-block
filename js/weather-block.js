(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, TextControl, ColorPicker } = wp.components;
    const { useState } = wp.element;

    // Inline-CSS im Block-Editor hinzufügen
    if (wp.element.render) {
        const style = document.createElement('style');
        style.textContent = `
            .weather-block-editor .weather-preview {
                padding: 20px;
                background: #f5f5f5;
                text-align: center;
            }
            .weather-icon {
                mask-size: contain;
                mask-repeat: no-repeat;
                mask-position: center;
                background-color: var(--weather-icon-color, #000000);
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
            iconColor: {
                type: 'string',
                default: '#000000'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { location, iconColor } = attributes;

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
                                            label: 'Ortsangabe',
                                            value: location,
                                            onChange: (newLocation) => {
                                                setAttributes({ location: newLocation });
                                            },
                                            help: 'Geben Sie einen Städtenamen oder einen OpenWeatherMap-Ortscode ein (z. B. "London", "Paris" oder eine Stadt-ID).'
                                        }
                                    ),
                                    wp.element.createElement(
                                        'div',
                                        { className: 'components-base-control' },
                                        [
                                            wp.element.createElement(
                                                'label',
                                                { className: 'components-base-control__label' },
                                                'Icon-Farbe'
                                            ),
                                            wp.element.createElement(
                                                ColorPicker,
                                                {
                                                    color: iconColor,
                                                    onChangeComplete: (color) => {
                                                        setAttributes({ iconColor: color.hex });
                                                    }
                                                }
                                            )
                                        ]
                                    )
                                ]
                            )
                        ),
                        wp.element.createElement(
                            'div',
                            { 
                                className: 'weather-preview',
                                style: { '--weather-icon-color': iconColor }
                            },
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