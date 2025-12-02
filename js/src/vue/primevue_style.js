// PrimeVue passthrough style configuration to use Bootstrap/Tabler styles
export default {
    button: {
        root: (options) => ({
            class: [
                'btn',
                `btn-${options.props.variant === 'ghost' ? 'ghost-' : (options.props.variant === 'outline' ? 'outline-' : '')}${options.props.severity || 'primary'}`,
                options.props.size === 'small' ? 'btn-sm' : (options.props.size === 'large' ? 'btn-lg' : ''),
            ]
        })
    },
    inputtext: {
        root: () => ({
            class: 'form-control',
        })
    }
};
