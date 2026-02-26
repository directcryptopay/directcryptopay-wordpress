(function (blocks, element, editor, components) {
    var el = element.createElement;
    var InspectorControls = editor.InspectorControls;
    var TextControl = components.TextControl;
    var PanelBody = components.PanelBody;
    var Button = components.Button;

    blocks.registerBlockType('directcryptopay/payment-button', {
        title: 'DirectCryptoPay Payment Button',
        icon: 'money-alt',
        category: 'widgets',
        attributes: {
            amount: {
                type: 'string',
                default: '10'
            },
            label: {
                type: 'string',
                default: 'Pay with Crypto'
            },
            currency: {
                type: 'string',
                default: 'USD'
            }
        },

        edit: function (props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            function onChangeAmount(newAmount) {
                setAttributes({ amount: newAmount });
            }

            function onChangeLabel(newLabel) {
                setAttributes({ label: newLabel });
            }

            function onChangeCurrency(newCurrency) {
                setAttributes({ currency: newCurrency });
            }

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: 'Payment Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'Amount (USD)',
                            value: attributes.amount,
                            onChange: onChangeAmount,
                            type: 'number',
                            min: '0',
                            step: '0.01'
                        }),
                        el(TextControl, {
                            label: 'Button Label',
                            value: attributes.label,
                            onChange: onChangeLabel
                        }),
                        el(TextControl, {
                            label: 'Currency',
                            value: attributes.currency,
                            onChange: onChangeCurrency,
                            help: 'Display currency (e.g., USD, EUR, GBP)'
                        })
                    )
                ),
                el('div', { key: 'editor-preview', className: 'dcp-block-editor' },
                    el('div', {
                        style: {
                            border: '2px dashed #0073aa',
                            borderRadius: '8px',
                            padding: '20px',
                            textAlign: 'center',
                            background: '#f0f6fc'
                        }
                    },
                        el('div', {
                            style: {
                                fontSize: '24px',
                                marginBottom: '10px'
                            }
                        }, '💎'),
                        el('h3', {
                            style: { margin: '10px 0', color: '#0073aa' }
                        }, 'DirectCryptoPay Button'),
                        el('p', {
                            style: { margin: '5px 0', fontSize: '14px', color: '#666' }
                        },
                            'Amount: $' + attributes.amount + ' ' + attributes.currency
                        ),
                        el(Button, {
                            isPrimary: true,
                            disabled: true,
                            style: { marginTop: '10px' }
                        }, attributes.label),
                        el('p', {
                            style: {
                                marginTop: '15px',
                                fontSize: '12px',
                                color: '#999',
                                fontStyle: 'italic'
                            }
                        }, 'Preview only - Payment button will appear on the published page')
                    )
                )
            ];
        },

        save: function () {
            return null; // Rendered via PHP
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor || window.wp.editor,
    window.wp.components
);
