<?php
define('EVENT_ID', 1);
define('PRICESET_ID', 38);
define('MAX_ALLOWED', 80);
define('ADULT_PREF', 171);
define('CHILD_PREF', 172);

require_once 'maxtickets.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function maxtickets_civicrm_config(&$config) {
  _maxtickets_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function maxtickets_civicrm_xmlMenu(&$files) {
  _maxtickets_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function maxtickets_civicrm_install() {
  _maxtickets_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function maxtickets_civicrm_uninstall() {
  _maxtickets_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function maxtickets_civicrm_enable() {
  _maxtickets_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function maxtickets_civicrm_disable() {
  _maxtickets_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function maxtickets_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _maxtickets_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function maxtickets_civicrm_managed(&$entities) {
  _maxtickets_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function maxtickets_civicrm_caseTypes(&$caseTypes) {
  _maxtickets_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function maxtickets_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _maxtickets_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function getCurrentCount($priceFields, $eventId) {
  $sql = "SELECT SUM(participant_count)
    FROM civicrm_event e
    INNER JOIN civicrm_participant p ON p.event_id = e.id
    INNER JOIN civicrm_participant_payment pp ON pp.participant_id = p.id
    INNER JOIN civicrm_line_item l ON l.contribution_id = pp.contribution_id
    WHERE l.price_field_id IN (%1)
    AND e.id = %2
    AND p.status_id IN (1)";
  $params = array(
    1 => array(implode(',', $priceFields), 'Text'),
    2 => array($eventId, 'Int'),
  );
  return CRM_Core_DAO::singleValueQuery($sql, $params);
}

function getPrices($toCheck) {
  $prices = array();
  $result = civicrm_api3('PriceField', 'get', array(
    'sequential' => 1,
    'price_set_id' => PRICESET_ID,
    'api.PriceFieldValue.get' => array(
      'price_field_id' => '$value.id',
    ),
  ));
  foreach ($result['values'] as $key => $priceFields) {
    foreach ($priceFields['api.PriceFieldValue.get']['values'] as $priceFieldValues) {
      if (in_array($priceFieldValues['price_field_id'], $toCheck)) {
        $prices[$priceFieldValues['price_field_id']][$priceFieldValues['id']] = $priceFieldValues['count'];
      }
    }
  }
  return $prices;
}

/**
 * Implementation of hook_civicrm_validateForm
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_validateForm
 */
function maxtickets_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == "CRM_Event_Form_Registration_Register" && in_array($form->_eventId, array(41, 42, 44))) {
    $validPrices = array(
      'price_' . ADULT_PREF => ADULT_PREF,
      'price_' . CHILD_PREF => CHILD_PREF,
    );
    $priceCount = 0;
    if (empty($fields['price_' . ADULT_PREF]) && empty($fields['price_' . CHILD_PREF])) {
      return;
    }
    else {
      if (!empty($fields['price_' . ADULT_PREF])) {
        $priceCount = $form->_values['fee'][ADULT_PREF]['options'][$fields['price_' . ADULT_PREF]]['count'];
      }
      if (!empty($fields['price_' . CHILD_PREF])) {
        $priceCount = $form->_values['fee'][CHILD_PREF]['options'][$fields['price_' . CHILD_PREF]]['count'];
      }
    }
    $prices = getPrices($validPrices);
    $currentCount = getCurrentCount($validPrices, $form->_eventId);
    if ($priceCount) {
      $currentCount = $currentCount + $priceCount;
    }
    CRM_Core_Session::singleton()->set('ticketCount', $currentCount);
    if ($currentCount >= MAX_ALLOWED) {
      $errors['email-Primary'] = ts("Sorry, the tickets for Adult/Child Preferences are currently sold out.");
    }
  }
  if ($formName == "CRM_Event_Form_Registration_AdditionalParticipant" && in_array($form->_eventId, array(41, 42, 44))) {
    $count = CRM_Core_Session::singleton()->get('ticketCount');
    $validPrices = array(
      'price_' . ADULT_PREF => ADULT_PREF,
      'price_' . CHILD_PREF => CHILD_PREF,
    );
    $priceCount = 0;
    if (empty($fields['price_' . ADULT_PREF]) && empty($fields['price_' . CHILD_PREF])) {
      return;
    }
    else {
      if (!empty($fields['price_' . ADULT_PREF])) {
        $priceCount = $form->_values['fee'][ADULT_PREF]['options'][$fields['price_' . ADULT_PREF]]['count'];
      }
      if (!empty($fields['price_' . CHILD_PREF])) {
        $priceCount = $form->_values['fee'][CHILD_PREF]['options'][$fields['price_' . CHILD_PREF]]['count'];
      }
    }
    $prices = getPrices($validPrices);
    foreach ($fields as $field => $fieldValue) {
      if (array_key_exists($field, $validPrices) && !empty($fields[$field])) {
        $count += $prices[$validPrices[$field]][$fieldValue];
      }
    }
    if ($priceCount) {
      $count = $count + $priceCount;
    }
    if ($count >= MAX_ALLOWED) {
      $errors['email-Primary'] = ts("Sorry, the tickets for Adult/Child Preferences are currently sold out.");
    }
  }
}

/**
 * Implementation of hook_civicrm_postProcess
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postProcess
 */
function maxtickets_civicrm_postProcess($formName, &$form) {
  if (in_array($formName, array("CRM_Event_Form_Registration_Register", "CRM_Event_Form_Registration_AdditionalParticipant"))
    && in_array($form->_eventId, array(41, 42, 44))) {
    $validPrices = array(
      'price_' . ADULT_PREF => ADULT_PREF,
      'price_' . CHILD_PREF => CHILD_PREF,
    );
    $priceCount = 0;
    if (empty($form->_submitValues['price_' . ADULT_PREF]) && empty($form->_submitValues['price_' . CHILD_PREF])) {
      return;
    }
    else {
      if (!empty($form->_submitValues['price_' . ADULT_PREF])) {
        $priceCount = $form->_values['fee'][ADULT_PREF]['options'][$fields['price_' . ADULT_PREF]]['count'];
      }
      if (!empty($form->_submitValues['price_' . CHILD_PREF])) {
        $priceCount = $form->_values['fee'][CHILD_PREF]['options'][$fields['price_' . CHILD_PREF]]['count'];
      }
    }
    $prices = getPrices($validPrices);
    $count = CRM_Core_Session::singleton()->get('ticketCount');
    foreach ($form->_submitValues as $field => $fieldValue) {
      if (array_key_exists($field, $validPrices) && !empty($form->_submitValues[$field])) {
        $count += $prices[$validPrices[$field]][$fieldValue];
      }
    }
    if ($priceCount) {
      $count = $count + $priceCount;
    }
    CRM_Core_Session::singleton()->set('ticketCount', $count);
  }
}
