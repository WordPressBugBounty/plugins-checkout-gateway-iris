(function () {
    // Ασφαλής έλεγχος για WC Blocks Registry & Settings.
    if (
        !window.wc ||
        !window.wc.wcBlocksRegistry ||
        !window.wc.wcSettings ||
        !window.wp ||
        !window.wp.element
    ) {
        return;
    }

    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getSetting } = window.wc.wcSettings;
    const { createElement, Fragment } = window.wp.element;

    // Τα data που γυρίζουν από το PHP: get_payment_method_data().
    const settings = getSetting('iris_payments_data', {}) || {};

    const labelText = settings.title || 'IRIS Payment';

    const Label = (props) => {
        const PMLabel = props.components.PaymentMethodLabel;
        return createElement(PMLabel, { text: labelText });
    };

    const Content = () => {
        return createElement(
            Fragment,
            null,
            createElement('p', null, settings.description || '')
        );
    };

    registerPaymentMethod({
        name: 'iris_payments',
        label: createElement(Label, null),
        ariaLabel: labelText,
        content: createElement(Content, null),
        edit: createElement(Content, null),
        canMakePayment: () => true,
        paymentMethodId: 'iris_payments',
        supports: {
            features: settings.supports || ['products'],
        },
    });
})();
