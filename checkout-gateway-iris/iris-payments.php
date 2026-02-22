<?php
/**
 * Plugin Name: Checkout Gateway for IRIS
 * Description: Payment gateway for WooCommerce.
 * Version:     1.4
 * Author:      vgdevsolutions
 * Author URI:  https://vgdevsolutions.gr/
 * Text Domain: checkout-gateway-iris
 * Requires Plugins: woocommerce
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Submenu κάτω από “VGDEVsolutions”.
 */
add_action( 'admin_menu', 'irisgw_add_iris_submenu', 10 );
function irisgw_add_iris_submenu() {
    add_submenu_page(
        'vgdevsolutions-dashboard',
        'Checkout Gateway for IRIS',
        'Checkout Gateway for IRIS',
        'manage_options',
        'iris-payments-dashboard',
        'irisgw_iris_dashboard_callback'
    );
}

function irisgw_iris_dashboard_callback() {
    echo '<div class="wrap">';
    echo '<h1>Checkout Gateway for IRIS Settings</h1>';
    echo '<p>This gateway is managed within <strong>WooCommerce &gt; Settings &gt; Payments</strong>.</p>';
    echo '<p><a class="button-primary" href="' . esc_url(
        admin_url( 'admin.php?page=wc-settings&tab=checkout&section=iris_payments' )
    ) . '">Go to Payment Settings</a></p>';
    echo '</div>';
}

/**
 * Init gateway μόνο αν είναι ενεργό το WooCommerce.
 */
add_action( 'plugins_loaded', 'irisgw_init_iris_payment_gateway' );
function irisgw_init_iris_payment_gateway() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    add_filter( 'woocommerce_payment_gateways', 'irisgw_add_iris_payment_gateway' );
    function irisgw_add_iris_payment_gateway( $gateways ) {
        $gateways[] = 'WC_Gateway_Iris_Payments';
        return $gateways;
    }

    class WC_Gateway_Iris_Payments extends WC_Payment_Gateway {

        /** Public για χρήση στα emails */
        public $completed_order_message;

        public function __construct() {
            $this->id           = 'iris_payments';
            $this->icon         = plugins_url( 'assets/payment-logo.png', __FILE__ );
            $this->has_fields   = false;

            $this->method_title       = __( 'Πληρωμή με IRIS', 'checkout-gateway-iris' );
            $this->method_description = __( 'Πληρωμή μέσω IRIS. Η παραγγελία τίθεται σε Αναμονή μέχρι την επιβεβαίωση της πληρωμής.', 'checkout-gateway-iris' );

            $this->init_form_fields();
            $this->init_settings();

            // Ρυθμίσεις.
            $this->title                   = $this->get_option( 'title' );
            $this->description             = $this->get_option( 'description' );
            $this->instructions            = $this->get_option( 'instructions' );
            $this->vat_number              = $this->get_option( 'vat_number' );
            $this->account_holder          = $this->get_option( 'account_holder' );
            $this->qr_code                 = $this->get_option( 'qr_code' );
            $this->reference_text          = $this->get_option( 'reference_text' );
            $this->vat_label               = $this->get_option( 'vat_label' );
            $this->account_holder_label    = $this->get_option( 'account_holder_label' );
            $this->completed_order_message = $this->get_option( 'completed_order_message' );

            if ( 'yes' !== $this->get_option( 'display_logo' ) ) {
                $this->icon = '';
            }

            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                [ $this, 'process_admin_options' ]
            );

            add_action(
                'woocommerce_thankyou_' . $this->id,
                [ $this, 'thankyou_page' ]
            );

            add_action(
                'woocommerce_checkout_order_processed',
                [ $this, 'set_order_on_hold' ],
                20,
                1
            );
        }

        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title'   => __( 'Ενεργοποίηση/Απενεργοποίηση', 'checkout-gateway-iris' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Ενεργοποίηση μεθόδου πληρωμής IRIS', 'checkout-gateway-iris' ),
                    'default' => 'yes',
                ],
                'display_logo' => [
                    'title'   => __( 'Εμφάνιση λογότυπου πληρωμής', 'checkout-gateway-iris' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Εμφάνιση λογότυπου στη σελίδα ταμείου (checkout)', 'checkout-gateway-iris' ),
                    'default' => 'yes',
                ],
                'title' => [
                    'title'       => __( 'Τίτλος', 'checkout-gateway-iris' ),
                    'type'        => 'text',
                    'description' => __( 'Ο τίτλος της μεθόδου που εμφανίζεται στο checkout.', 'checkout-gateway-iris' ),
                    'default'     => __( 'Πληρωμή με IRIS', 'checkout-gateway-iris' ),
                    'desc_tip'    => true,
                ],
                'description' => [
                    'title'       => __( 'Περιγραφή', 'checkout-gateway-iris' ),
                    'type'        => 'textarea',
                    'description' => __( 'Κείμενο που βλέπει ο πελάτης κάτω από τη μέθοδο πληρωμής στο checkout.', 'checkout-gateway-iris' ),
                    'default'     => __( 'Πληρώστε απευθείας με IRIS. Η παραγγελία σας θα τεθεί σε αναμονή μέχρι να επιβεβαιωθεί η πληρωμή.', 'checkout-gateway-iris' ),
                    'desc_tip'    => true,
                ],
                'instructions' => [
                    'title'       => __( 'Οδηγίες (Thank You σελίδα)', 'checkout-gateway-iris' ),
                    'type'        => 'textarea',
                    'description' => __( 'Οδηγίες που θα εμφανίζονται στη σελίδα ολοκλήρωσης παραγγελίας (Thank You).', 'checkout-gateway-iris' ),
                    'default'     => __( 'Χρησιμοποιήστε το ID Παραγγελίας ως αιτιολογία πληρωμής.', 'checkout-gateway-iris' ),
                    'desc_tip'    => true,
                ],
                'vat_number' => [
                    'title'       => __( 'ΑΦΜ', 'checkout-gateway-iris' ),
                    'type'        => 'text',
                    'description' => __( 'Εισάγετε το ΑΦΜ σας.', 'checkout-gateway-iris' ),
                    'default'     => '123456789',
                ],
                'account_holder' => [
                    'title'       => __( 'Όνομα Δικαιούχου Λογαριασμού', 'checkout-gateway-iris' ),
                    'type'        => 'text',
                    'description' => __( 'Εισάγετε το όνομα του δικαιούχου του τραπεζικού λογαριασμού.', 'checkout-gateway-iris' ),
                    'default'     => __( 'Παράδειγμα Δικαιούχου', 'checkout-gateway-iris' ),
                ],
                'qr_code' => [
                    'title'       => __( 'URL εικόνας QR', 'checkout-gateway-iris' ),
                    'type'        => 'text',
                    'description' => __( 'Δώστε το URL της εικόνας QR για πληρωμή.', 'checkout-gateway-iris' ),
                    'default'     => 'https://example.com/default-qr.png',
                ],
                'reference_text' => [
                    'title'       => __( 'Κείμενο Αιτιολογίας', 'checkout-gateway-iris' ),
                    'type'        => 'textarea',
                    'description' => __( 'Κείμενο με οδηγίες προς τον χρήστη να χρησιμοποιεί το ID Παραγγελίας στην αιτιολογία πληρωμής.', 'checkout-gateway-iris' ),
                    'default'     => __( 'Παρακαλούμε χρησιμοποιήστε το ID Παραγγελίας σας ως αιτιολογία πληρωμής.', 'checkout-gateway-iris' ),
                ],
                'vat_label' => [
                    'title'       => __( 'Ετικέτα ΑΦΜ', 'checkout-gateway-iris' ),
                    'type'        => 'text',
                    'description' => __( 'Ετικέτα που θα εμφανίζεται πριν από το ΑΦΜ.', 'checkout-gateway-iris' ),
                    'default'     => __( 'ΑΦΜ:', 'checkout-gateway-iris' ),
                ],
                'account_holder_label' => [
                    'title'       => __( 'Ετικέτα Δικαιούχου Λογαριασμού', 'checkout-gateway-iris' ),
                    'type'        => 'text',
                    'description' => __( 'Ετικέτα που θα εμφανίζεται πριν από το όνομα δικαιούχου.', 'checkout-gateway-iris' ),
                    'default'     => __( 'Δικαιούχος:', 'checkout-gateway-iris' ),
                ],
                'completed_order_message' => [
                    'title'       => __( 'Μήνυμα για Ολοκληρωμένη Παραγγελία (email)', 'checkout-gateway-iris' ),
                    'type'        => 'textarea',
                    'description' => __( 'Κείμενο που θα εμφανίζεται στο email ολοκληρωμένης παραγγελίας (customer_completed_order).', 'checkout-gateway-iris' ),
                    'default'     => __( 'Η παραγγελία σας έχει ολοκληρωθεί και έχει δοθεί στην courier προς αποστολή.', 'checkout-gateway-iris' ),
                    'desc_tip'    => true,
                ],
            ];
        }

        public function process_admin_options() {
            parent::process_admin_options();

            update_option( 'irisgw_reference_text', $this->get_option( 'reference_text' ) );
            update_option( 'irisgw_vat_label', $this->get_option( 'vat_label' ) );
            update_option( 'irisgw_account_holder_label', $this->get_option( 'account_holder_label' ) );
            update_option( 'irisgw_completed_order_message', $this->get_option( 'completed_order_message' ) );
        }

        public function thankyou_page() {
            echo '<p>' . esc_html( $this->reference_text ) . '</p>';
            echo '<p><strong>' . esc_html( $this->vat_label ) . '</strong> ' . esc_html( $this->vat_number ) . '</p>';
            echo '<p><strong>' . esc_html( $this->account_holder_label ) . '</strong> ' . esc_html( $this->account_holder ) . '</p>';

            if ( $this->qr_code ) {
                $attachment_id = attachment_url_to_postid( $this->qr_code );
                if ( $attachment_id ) {
                    echo wp_get_attachment_image(
                        $attachment_id,
                        [ 170, 170 ],
                        false,
                        [
                            'alt'   => 'QR Code',
                            'style' => 'height:170px;',
                        ]
                    );
                } else {
                    echo '<img src="' . esc_url( $this->qr_code ) . '" alt="QR Code" style="height:170px;" />';
                }
            }
        }

        public function set_order_on_hold( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order && $order->get_payment_method() === $this->id ) {
                $order->update_status(
                    'on-hold',
                    __( 'Awaiting Checkout Payment', 'checkout-gateway-iris' )
                );
            }
        }

        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            $order->update_status(
                'on-hold',
                __( 'Awaiting Checkout Payment', 'checkout-gateway-iris' )
            );

            wc_reduce_stock_levels( $order_id );
            WC()->cart->empty_cart();

            return [
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            ];
        }
    }
}

/**
 * Settings link στο Plugins list.
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'irisgw_payments_add_action_links' );
function irisgw_payments_add_action_links( $links ) {
    $settings_link = '<a href="' . esc_url(
        admin_url( 'admin.php?page=wc-settings&tab=checkout&section=iris_payments' )
    ) . '">' . __( 'Settings', 'checkout-gateway-iris' ) . '</a>';

    array_unshift( $links, $settings_link );
    return $links;
}

/**
 * Frontend CSS για το logo.
 */
add_action( 'wp_enqueue_scripts', 'irisgw_enqueue_css' );
function irisgw_enqueue_css() {
    wp_enqueue_style(
        'iris-payments-css',
        plugins_url( 'assets/css/iris-payment.css', __FILE__ ),
        [],
        '1.0',
        'all'
    );
}

/**
 * Extra περιεχόμενο στα emails για IRIS.
 */
add_action( 'woocommerce_email_order_details', 'irisgw_add_iris_payment_details', 10, 4 );
function irisgw_add_iris_payment_details( $order, $sent_to_admin, $plain_text, $email ) {
    if ( ! $order || $order->get_payment_method() !== 'iris_payments' || ! isset( $email->id ) ) {
        return;
    }

    if ( 'customer_on_hold_order' === $email->id ) {
        $iris_gateway         = new WC_Gateway_Iris_Payments();
        $reference_text       = $iris_gateway->reference_text;
        $vat_label            = $iris_gateway->vat_label;
        $vat_number           = $iris_gateway->vat_number;
        $account_holder_label = $iris_gateway->account_holder_label;
        $account_holder       = $iris_gateway->account_holder;
        $qr_code              = $iris_gateway->qr_code;

        echo '<p>' . esc_html( $reference_text ) . '</p>';
        echo '<p><strong>' . esc_html( $vat_label ) . '</strong> ' . esc_html( $vat_number ) . '</p>';
        echo '<p><strong>' . esc_html( $account_holder_label ) . '</strong> ' . esc_html( $account_holder ) . '</p>';

        if ( ! empty( $qr_code ) ) {
            $attachment_id = attachment_url_to_postid( $qr_code );
            if ( $attachment_id ) {
                echo wp_get_attachment_image(
                    $attachment_id,
                    [ 170, 170 ],
                    false,
                    [
                        'alt'   => 'QR Code',
                        'style' => 'height:170px; margin-bottom:10px;',
                    ]
                );
            } else {
                echo '<img src="' . esc_url( $qr_code ) . '" alt="QR Code" style="height:170px; margin-bottom:10px;" />';
            }
        }
    } elseif ( 'customer_completed_order' === $email->id ) {
        $iris_gateway  = new WC_Gateway_Iris_Payments();
        $completed_msg = $iris_gateway->completed_order_message;
        if ( $completed_msg ) {
            echo '<p>' . esc_html( $completed_msg ) . '</p>';
        }
    }
}

/* ======================================================================
 *           WooCommerce CART / CHECKOUT BLOCKS SUPPORT
 * ==================================================================== */

/**
 * Δήλωση συμβατότητας με τα Cart/Checkout Blocks.
 */
add_action(
    'before_woocommerce_init',
    function () {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                __FILE__,
                true
            );
        }
    }
);

/**
 * Server-side integration για το Checkout Block (IRIS).
 */
add_action( 'woocommerce_blocks_loaded', 'irisgw_register_iris_block_support' );

function irisgw_register_iris_block_support() {

    if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    if ( ! class_exists( 'IRIS_Blocks_Payment_Method' ) ) {

        final class IRIS_Blocks_Payment_Method extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {

            /**
             * ΠΡΕΠΕΙ να ταιριάζει με το $this->id του gateway (iris_payments)
             * και με το JS registration name.
             *
             * @var string
             */
            protected $name = 'iris_payments';

            /**
             * Ρυθμίσεις gateway.
             *
             * @var array
             */
            protected $settings = [];

            /**
             * Φορτώνει τις ρυθμίσεις του gateway.
             */
            public function initialize() {
                $this->settings = get_option( 'woocommerce_' . $this->name . '_settings', [] );

                // Register script εδώ ώστε να είναι διαθέσιμο όταν χρειαστεί.
                $handle     = 'iris-payments-blocks';
                $script_rel = 'assets/js/iris-blocks.js';
                $script_abs = plugin_dir_path( __FILE__ ) . $script_rel;

                wp_register_script(
                    $handle,
                    plugins_url( $script_rel, __FILE__ ),
                    [ 'wc-blocks-registry', 'wc-settings', 'wp-element' ],
                    file_exists( $script_abs ) ? filemtime( $script_abs ) : '1.0.0',
                    true
                );
            }

            /**
             * Αν είναι ενεργό το gateway στο WooCommerce settings.
             */
            public function is_active() {
                $enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'no';
                return 'yes' === $enabled;
            }

            /**
             * Ποιο JS handle θα φορτωθεί στο frontend.
             */
            public function get_payment_method_script_handles() {
                return [ 'iris-payments-blocks' ];
            }

            public function get_payment_method_script_handles_for_admin() {
                return $this->get_payment_method_script_handles();
            }

            /**
             * Data που θα περάσουν στο JS μέσω wcSettings.getSetting('iris_payments_data').
             */
            public function get_payment_method_data() {
                $title       = isset( $this->settings['title'] ) && $this->settings['title'] !== ''
                    ? $this->settings['title']
                    : __( 'Πληρωμή με IRIS', 'checkout-gateway-iris' );

                $description = isset( $this->settings['description'] ) && $this->settings['description'] !== ''
                    ? $this->settings['description']
                    : __( 'Πληρωμή μέσω IRIS. Μετά την ολοκλήρωση της παραγγελίας θα δείτε τις οδηγίες πληρωμής και το QR code.', 'checkout-gateway-iris' );

                return [
                    'title'       => $title,
                    'description' => $description,
                    'supports'    => [ 'products' ],
                    'gatewayId'   => $this->name,
                ];
            }
        }
    }

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            $payment_method_registry->register( new \IRIS_Blocks_Payment_Method() );
        }
    );
}
