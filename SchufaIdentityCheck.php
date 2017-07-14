<?php

/*
Plugin Name: Schufa Identity Check
Plugin URI: https://identitaetscheck-plugin.de
Description: Gemäß dem seit 01.04.2016 geltendem Gesetz für Onlinehandel mit Waren, die nicht an Jugendliche unter 18 Jahren abgegeben werden dürfen, wird mit diesem Plugin eine Schufa Abfrage für das Produkt Identitätscheck Premium durchgeführt. Schlägt diese Abfrage fehl, wird der Kauf mit einer Fehlermeldung abgebrochen.
Version: 2.9
Author: Hendrik Bäker
Author URI: http://baeker-it.de
License: GNU GPL v2
*/
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && function_exists('openssl_pkcs12_read') && function_exists('curl_init')) {
    add_filter('woocommerce_checkout_fields', 'order_fields');
    add_action('woocommerce_checkout_update_order_meta', 'birthdate_update_order_meta');
    add_action('woocommerce_checkout_process', 'schufaPruefung');
    add_action('admin_menu', 'SchufaIdentityCheck');
    add_action('show_user_profile', 'show_SCHUFA_Status');
    add_action('edit_user_profile', 'show_SCHUFA_Status');
    add_action('woocommerce_checkout_process', 'add_usermeta');
    add_action('woocommerce_admin_order_data_after_billing_address', 'schufaIdentityCheckBackendDisplay');

    class SCHUFA_IDCheck
    {

        private $url;

        private $curl;

        public $result;

        private $postdata;

        private $neededFields;

        function __construct($mode = false, $data = false)
        {
            $this->neededFields = [
                'title',
                'first_name',
                'last_name',
                'street',
                'zipcode',
                'city',
                'birthdate'
            ];

            $this->result = new stdClass();

            if (!$mode and !$data) {
                $this->result = ['result' => false, 'message' => 'Fehlende Parameter'];
                return;
            }

            if (!$this->collectUserData($data) and $mode != 'test') {
                $this->result = ['result' => false, 'message' => 'Falsche Konfiguration'];
                return;
            }

            switch ($mode) {
                case 'check':
                    $this->url = "https://baeker-it.de/api/schufa/request";
                    $this->postdata['testmode'] = false;
                    break;
                case 'status':
                    $this->url = "https://baeker-it.de/api/schufa/status";
                    $this->postdata['testmode'] = false;
                    break;
                case 'manual':
                    $this->url = "https://baeker-it.de/api/schufa/check";
                    $this->postdata['testmode'] = false;
                    break;
                case 'test':
                    $this->url = "https://baeker-it.de/api/schufa/request";
                    $this->postdata['testmode'] = true;
                    $this->postdata['index'] = 0;
                    break;
            }

            $this->postdata['user'] = $data['user'];
            $this->postdata['schufa_password'] = $data['schufa_password'];
            if ($mode == 'test') {
                do {
                    $this->initCurl();
                    $result = json_decode(curl_exec($this->curl));
                    $this->results[] = $result;
                    $this->postdata['index'] += 1;
                } while ($this->postdata['index'] != 13);

            } else {
                $this->initCurl();
                $this->result = json_decode(curl_exec($this->curl));
                if ($mode == 'check') {
                    $this->checkCustomer();
                }
            }

        }

        public function checkTestResult($result)
        {
            if ($result->result) {
                echo "<tr><td><span style='color:green'>Positiv</span></td></tr>";
            } else {
                echo "<tr><td><span style='color:red'>Negativ</span></td><td>" . $result->message . "</td></tr>";
            }
        }

        public function getCustomerStatus($result)
        {
            if (!$result->result) {
                echo "<h2 style='color:red'>Negativ</h2>";
            } else {
                echo "<h2 style='color:green'>Positiv</h2>";
            }
        }

        public function checkCustomer()
        {
            if (!$this->result->result) {
                wc_add_notice($this->result->message, 'error');
            }
        }

        private function collectUserData($data)
        {
            foreach ($this->neededFields as $field) {
                if (!array_key_exists($field, $data)) {
                    return false;
                } else {
                    $this->postdata[$field] = $data[$field];
                }
            }
            return true;
        }

        private function initCurl()
        {
            $this->curl = curl_init();
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($this->curl, CURLOPT_URL, $this->url);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->postdata);
        }

    }

    function order_fields($fields)
    {
        if (!strpos($_SERVER['REQUEST_URI'], "en")) {
            $order = array(
                "billing_title",
                "billing_first_name",
                "billing_last_name",
                "billing_company",
                "billing_birthdate",
                "billing_address_1",
                "billing_address_2",
                "billing_postcode",
                "billing_city",
                "billing_country",
                "billing_email",
                "billing_phone"

            );
            foreach ($order as $field) {
                @$ordered_fields[$field] = $fields["billing"][$field];
            }
            $fields["billing"] = $ordered_fields;
            $fields["billing"]['billing_birthdate']['label'] = __('Geburtsdatum (TT.MM.JJJJ): ');
            $fields["billing"]['billing_birthdate']['label_class'] = array('billing-birthdate');
            $fields["billing"]['billing_birthdate']['required'] = true;
            $fields["billing"]['billing_birthdate']['class'] = array('input-text billing-birthdate');
            $fields["billing"]['billing_birthdate']['type'] = 'text';
        }
        return $fields;

    }

    function schufaIdentityCheckBackendDisplay($order)
    {
        ?>
        <div class="wrap">
            <h4>Identitätscheck Plugin:</h4>
            <p>Dieses Feature folgt</p>
        </div><?php
    }

    function show_SCHUFA_Status($user, $return = false)
    {
        $fields = [
            "billing_first_name" => 'first_name',
            "billing_last_name" => 'last_name',
            "billing_birthdate" => 'birthdate',
            "billing_address_1" => 'address_1',
            "billing_postcode" => 'postcode',
            "billing_city" => 'city',
        ];
        foreach ($fields as $field => $postkey) {
            $data[$postkey] = get_user_meta($user->ID, $field);
        }
        $status = new SCHUFA_IDCheck('status', $data);
        if ($return) {
            return $return;
        }
        ?>
        <h3>Identitätscheck Plugin Status:</h3>
        <table class="form-table">
            <tr>
                <th>SCHUFA Prüfung:</th>
                <td><?php
                    if (!$status):?>
                        <span style="color:red;">Identität noch nicht bestätigt</span>
                    <?php else: ?>
                        <span style="color:green;"><strong><b>Identität geprüft und bestätigt</b></strong></span>
                    <?php endif;
                    ?></td>
            </tr>
            <tr>
                <th>Geburtsdatum:</th>
                <td><?php
                    if (get_user_meta($user->ID, 'billing_birthdate', true) != ""): ?>
                        <span><?php echo get_user_meta($user->ID, 'billing_birthdate', true); ?></span>
                    <?php else: ?>
                        <span>Dieser Kunde hat noch kein Geburtsdatum hinterlegt</span>
                    <?php endif; ?></td>
            </tr>
        </table>
        <?php if (get_user_meta($user->ID, 'SCHUFA', true) != 1) { ?>
        <input type="hidden" value="0" name="SCHUFA"/>
        <input type="submit" class="btn btn-danger" value="Benutzer erneut Prüfen lassen"/>
    <?php } else { ?>
        <input type="hidden" value="1" name="SCHUFA"/>
        <input type="submit" class="btn btn-primary" value="Benutzer Freischalten"/>
    <?php }
    }

    add_action('edit_user_profile_update', 'update_user');

    function update_user($user_id)
    {
        $status = (int)filter_input(INPUT_POST, 'SCHUFA');
        update_user_meta($user_id, 'SCHUFA', $status);
        return;
    }

    function SchufaIdentityCheck()
    {
        add_menu_page('Schufa Identitätscheck', 'Schufa Identitätscheck', 'edit_posts', 'schufa_identity_check', 'show_settings', NULL, 3);
        add_submenu_page('schufa_identity_check', 'Plugin Einstellungen', 'Plugin Einstellungen', 'manage_options', 'plugin-einstellungen', 'schufa_identity_check_settings');
    }

    function show_settings()
    {
        $settings = get_option('schufa_setting');
        ?>
        <div class="wrap">
        <h2>Teilnehmerdaten:</h2>
        <p>Kennwort: <?php echo $settings['schufa_password']; ?></p>
        <br/>
        <h2>Manuelle SCHUFA Abfrage:</h2>
        <form action="" method="POST">
            <input type="submit" name="abnahmetest" value="SCHUFA Abnahmetest"/>
        </form>
        <?php
        if (isset($_POST['submit']) || isset($_POST['abnahmetest'])): ?>
            <pre>
                <?php demo(); ?>
            </pre>
            </div>
        <?php endif;
    }

    add_action('woocommerce_checkout_after_order_review', 'datenuebergabeschufa');

    function datenuebergabeschufa($checkout)
    {

        echo '<div id="additional-checkboxes"><h3>' . __('Altersverifikation: ') . '</h3>';

        woocommerce_form_field('schufa_checkbox', array(
            'type' => 'checkbox',
            'class' => array('input-checkbox'),
            'label' => __('Ich willige ein, dass meine persönlichen Daten zum Zweck der Altersprüfung an die SCHUFA Holding AG (Kormoranweg 5, 65201 Wiesbaden) übermittelt werden. Eine Speicherung meiner Daten im SCHUFA-Datenbestand oder ein weiterer Datenaustausch findet nicht statt. Nur das Ergebnis der Prüfung meines Alters wird bei der SCHUFA gespeichert. Eine Bonitätsprüfung erfolgt ausdrücklich nicht! Nähere Informationen finden Sie unter <a href="http://www.meineschufa.de" target="_blank">www.meineschufa.de</a>'),
            'label_class' => array('schufa_identity_check_agreement'),
            'required' => true,
        ));

        echo '</div>';
    }


    add_action('woocommerce_checkout_process', 'datenubergabeschufa_process');

    function datenubergabeschufa_process()
    {

        // Check if set, if its not set add an error.
        if ($_POST['schufa_checkbox'] != 1) {
            wc_add_notice('<strong>Altersverifikation</strong> ' . __('is a required field.', 'woocommerce'), 'error');
        }
    }

    add_action('woocommerce_checkout_update_order_meta', 'datenubergabeschufa_update_order_meta');

    function datenuebergabeschufa_update_order_meta($order_id)
    {
        if ($_POST['schufa_checkbox']) update_post_meta($order_id, 'Altersverifikation', esc_attr($_POST['schufa_checkbox']));
    }

    function demo()
    {
        $settings = get_option('schufa_setting');
        if (isset($_POST['abnahmetest'])) {
            $result = new SCHUFA_IDCheck('test', ['user' => $settings['user'], 'schufa_password' => $settings['schufa_password']]);
            echo "<table>";
            echo "<pre>";
            foreach ($result->results as $results) {
                $result->checkTestResult($results);
            }
            echo "</table>";
        } else {
            $post = array(
                'title' => filter_input(INPUT_POST, 'title'),
                'first_name' => filter_input(INPUT_POST, 'first_name'),
                'last_name' => filter_input(INPUT_POST, 'last_name'),
                'birthdate' => filter_input(INPUT_POST, 'birthdate'),
                'zipcode' => filter_input(INPUT_POST, 'zipcode'),
                'city' => filter_input(INPUT_POST, 'city'),
                'street' => filter_input(INPUT_POST, 'street'),
                'country' => filter_input(INPUT_POST, 'billing_country'),
                'user' => $settings['user'],
                'schufa_password' => $settings['schufa_password']
            );
            if ($post['country'] == null) {
                $post['country'] = 'DEU';
            }
            $result = new SCHUFA_IDCheck('check', $post);
            $result->getCustomerStatus($result->result);
        }
    }

    function schufaPruefung()
    {
        if (get_user_meta(get_current_user_id(), 'SCHUFA', true) != 1) {
            if (filter_input(INPUT_POST, 'billing_title') == NULL) {
                $value['title'] = "U";
            } else {
                switch (filter_input(INPUT_POST, 'billing_title')) {
                    case 1:
                        $value['title'] = 'M';
                        break;
                    case 0:
                        $value['title'] = 'W';
                        break;
                    default:
                        $value['title'] = 'U';
                }
            }
            $value['first_name'] = filter_input(INPUT_POST, 'billing_first_name');
            $value['last_name'] = filter_input(INPUT_POST, 'billing_last_name');
            $value['birthdate'] = date("d.m.Y", strtotime(filter_input(INPUT_POST, 'billing_birthdate')));
            $value['zipcode'] = filter_input(INPUT_POST, 'billing_postcode');
            $value['city'] = filter_input(INPUT_POST, 'billing_city');
            $value['street'] = filter_input(INPUT_POST, 'billing_address_1');
            $value['country'] = filter_input(INPUT_POST, 'billing_country');
            if ($value['country'] != null) {
                if ($value['country'] != 'DE') {
                    return true;
                }
            }
            $settings = get_option('schufa_setting');
            $value['user'] = $settings['user'];
            $value['schufa_password'] = $settings['schufa_password'];
            $value['testmode'] = false;
            $result = new SCHUFA_IDCheck('check', $value);
        }
    }

    function schufa_identity_check_settings()
    {
        ?>
        <div class="wrap">
            <h2>Identitätscheck Rechenzentrum/API Verbindungsdaten:</h2>
            <form method="post" action="options.php" enctype="multipart/form-data">
                <?php settings_fields('schufa_settings'); ?>
                <?php $schufa_settings = get_option('schufa_setting'); ?>
                <h3>Teilnehmerdaten:</h3>
                </p>
                <p><label>E-Mail Adresse:</label><input type="text" name="schufa_setting[user]"
                                                        value="<?php echo $schufa_settings['user']; ?>" id="tp"/>
                </p>
                <p><label>Teilnehmerkennwort:</label><input type="text" name="schufa_setting[schufa_password]"
                                                            value="<?php echo $schufa_settings['schufa_password'] ?>"/>
                </p>
                <input type="submit" class="button-primary" name="submit" value="Einstellungen speichern"/>
            </form>
        </div>
        <?php
    }

    add_action('admin_init', 'schufa_age_verify_init');
    function schufa_age_verify_init()
    {
        register_setting('schufa_settings', 'schufa_setting', 'schufa_validate');
    }

    function birthdate_update_order_meta($order_id)
    {
        if ($_POST['billing_birthdate']) update_post_meta($order_id, 'Geburtsdatum', esc_attr($_POST['billing_birthdate']));
    }

    function schufa_validate($input)
    {
        return $input;
    }
} else {
    add_action('admin_menu', 'SchufaFallbackMenu');
    function SchufaFallbackMenu()
    {
        add_menu_page('Schufa Identitätscheck', 'Schufa Identitätscheck', 'edit_posts', 'schufa_identity_check', 'schufa_fallback', NULL, 3);
    }

    function schufa_fallback()
    {
        ?>
        <div class="wrap">
        <h3 style="color:red">Das Plugin kann nicht gestartet werden</h3>
        Bitte stellen Sie sicher dass:
        <ul>
            <?php
            if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))):echo "<li style='color:red'>";
            else: echo "<li style='color:green'>"; endif;
            ?>
            WooCommerce installiert und aktiviert ist</li>
            <?php
            if (!function_exists('curl_init')):echo "<li style='color:red'>";
            else: echo "<li style='color:green'>"; endif;
            ?>
            cURL aktiv ist</li>
        </ul>
        <h4>Bestellungen werden im Moment noch ohne SCHUFA Abfrage angenommen</h4>
        </div><?php
    }
}