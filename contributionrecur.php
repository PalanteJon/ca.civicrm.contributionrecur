<?php

require_once 'contributionrecur.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function contributionrecur_civicrm_config(&$config) {
  _contributionrecur_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function contributionrecur_civicrm_xmlMenu(&$files) {
  _contributionrecur_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function contributionrecur_civicrm_install() {
  _contributionrecur_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function contributionrecur_civicrm_uninstall() {
  _contributionrecur_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function contributionrecur_civicrm_enable() {
  _contributionrecur_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function contributionrecur_civicrm_disable() {
  _contributionrecur_civix_civicrm_disable();
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
function contributionrecur_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _contributionrecur_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function contributionrecur_civicrm_managed(&$entities) {
  _contributionrecur_civix_civicrm_managed($entities);
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
function contributionrecur_civicrm_caseTypes(&$caseTypes) {
  _contributionrecur_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function contributionrecur_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _contributionrecur_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function _contributionrecur_civicrm_domain_info($key) {
  static $domain;
  if (empty($domain)) {
    $domain = civicrm_api('Domain', 'getsingle', array('version' => 3, 'current_domain' => TRUE));
  }
  switch($key) {
    case 'version':
      return explode('.',$domain['version']);
    default:
      if (!empty($domain[$key])) {
        return $domain[$key];
      }
      $config_backend = unserialize($domain['config_backend']);
      return $config_backend[$key];
  }
}

function _contributionrecur_civicrm_nscd_fid() {
  $version = _contributionrecur_civicrm_domain_info('version');
  return (($version[0] <= 4) && ($version[1] <= 3)) ? 'next_sched_contribution' : 'next_sched_contribution_date';
}

/*
 * hook_civicrm_buildForm
 * Do a Drupal 7 style thing so we can write smaller functions
 */
function contributionrecur_civicrm_buildForm($formName, &$form) {
  $monthly_setting = civicrm_api3('Setting', 'getvalue', array('name' => 'contributionrecur_monthly'));
  if (empty($monthly_setting)) {
    global $civicrm_setting;
    $monthly_setting = $civicrm_setting['Recurring Contribution Preferences']['contributionrecur_monthly'];
  }
  if (empty($monthly_setting)) {
    return;
  }
  // otherwise, restrict recurring contributions to the days in settings
  $fname = 'contributionrecur_'.$formName;
  switch($formName) {
    case 'CRM_Event_Form_Participant':
    case 'CRM_Member_Form_Membership':
    case 'CRM_Contribute_Form_Contribution':
      // override normal convention, deal with all these backend credit card contribution forms the same way
      $fname = 'contributionrecur_civicrm_buildForm_CreditCard_Backend';
      break;

    case 'CRM_Contribute_Form_Contribution_Main':
    case 'CRM_Event_Form_Registration_Register':
      // override normal convention, deal with all these front-end contribution forms the same way
      $fname = 'contributionrecur_civicrm_buildForm_Contribution_Frontend';
      break;
  }
  if (function_exists($fname)) {
    $fname($form);
  }
  // else { echo $fname; die(); }
}

/*
 * hook_civicrm_pageRun
 *
 * Similar for pageRuns
 */
function contributionrecur_civicrm_pageRun(&$page) {
  $fname = 'contributionrecur_pageRun_'.$page->getVar('_name');
  if (function_exists($fname)) {
    $fname($page);
  }
  else { echo $fname;
    // watchdog('civicustom','hook_civicrm_pageRun for page @name',array('@name' => $fname));
  }
}

function contributionrecur_civicrm_buildForm_Contribution_Frontend(&$form) {
  // ignore this form if I have no payment processor or there's no recurring option
  if (empty($form->_paymentProcessors)) {
    return;
  }
  if (empty($form->_elementIndex['is_recur'])) {
    return;
  }
  $monthly_setting = civicrm_api3('Setting', 'getvalue', array('name' => 'contributionrecur_monthly'));
  if (empty($monthly_setting)) {
    global $civicrm_setting;
    $monthly_setting = $civicrm_setting['Recurring Contribution Preferences']['contributionrecur_monthly'];
  }
  // If a form enables recurring, set recurring to the default and required
  $form->setDefaults(array('is_recur' => 1)); // make recurring contrib default to true
  $form->addRule('is_recur', ts('You can only use this form to make recurring contributions.'), 'required');
  $start_days = explode(',',$monthly_setting);
  $start_dates = array(); // actual date options
  $start_date = time() + 60*60; // force tomorrow if I'm too close to the end of today
  for ($j = 0; $j < count($start_days); $j++) {
    $i = 0;  // so I don't get into an infinite loop somehow ..
    while(($i++ < 60) && !in_array($dp['mday'],$start_days)) {
      $start_date += (24 * 60 * 60);
      $dp = getdate($start_date);
    }
    $start_dates[date('Y-m-d',$start_date)] = strftime('%B %e, %Y',$start_date);
    $start_date += (24 * 60 * 60);
    $dp = getdate($start_date);
  }
  $form->addElement('select', 'start_date', ts('Date of first contribution.'), $start_dates);
  CRM_Core_Region::instance('page-body')->add(array(
    'template' => 'CRM/Contributionrecur/StartDate.tpl'
  ));
  CRM_Core_Resources::singleton()->addScriptFile('ca.civicrm.contributionrecur', 'js/front.js');
}

// function _contributionrecur_get_next_dates

/* 
 * add some functionality to the update subscription form for recurring contributions 
 *
 * Todo: make the available new fields configurable
 */
function contributionrecur_CRM_Contribute_Form_UpdateSubscription(&$form) {
  // only do this if the user is allowed to edit contributions. A more stringent permission might be smart.
  if (!CRM_Core_Permission::check('edit contributions')) {
    return;
  }
  $crid = CRM_Utils_Request::retrieve('crid', 'Integer', $form, FALSE);
  /* get the recurring contribution record and the contact record, or quit */
  try {
    $recur = civicrm_api3('ContributionRecur', 'getsingle', array('id' => $crid));
  } 
  catch (CiviCRM_API3_Exception $e) {
    return;
  }
  try {
    $contact = civicrm_api3('Contact', 'getsingle', array('id' => $recur['contact_id']));
  } 
  catch (CiviCRM_API3_Exception $e) {
    return;
  }
  // turn off default notification checkbox, most will want to hide it as well.
  $defaults = array('is_notify' => 0);
  $edit_fields = array('contribution_status_id', 'next_sched_contribution_date');
  foreach($edit_fields as $fid) {
    $defaults[$fid] = $recur[$fid];
  }
  $form->addElement('static','contact',$contact['display_name']);
  $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
  $form->addElement('select', 'contribution_status_id', ts('Status'),$contributionStatus);
  $form->addDateTime('next_sched_contribution_date', ts('Next Scheduled Contribution'));
  // $pp_id = $form->_paymentProcessor['id']; // get my pp
  // $processors = civicustom_civicrm_payment_processor_ids($crid);
  // $form->addElement('select', 'payment_processor_id', ts('Payment processor'),$processors);
  // 'payment_processor_id' => $pp_id, 
  $form->setDefaults($defaults);
  CRM_Core_Region::instance('page-body')->add(array(
    'template' => 'CRM/Contributionrecur/Subscription.tpl',
  ));
  CRM_Core_Resources::singleton()->addScriptFile('ca.civicrm.contributionrecur', 'js/subscription.js');
}

/*
 * Display extra info on the recurring contribution view
 */
function contributionrecur_pageRun_CRM_Contribute_Page_ContributionRecur($page) {
  // get the recurring contribution record or quit 
  $crid = CRM_Utils_Request::retrieve('id', 'Integer', $page, FALSE);
  try {
    $recur = civicrm_api3('ContributionRecur', 'getsingle', array('id' => $crid));
  } 
  catch (CiviCRM_API3_Exception $e) {
    return;
  }
  // show iats custom codes table data, if available
  $extra = _contributionrecur_get_iats_extra($recur);
  if (empty($extra)) {
    return;
  }
  $template = CRM_Core_Smarty::singleton();
  foreach($extra as $key => $value) {
    $template->assign( $key, $value);
  }
  CRM_Core_Region::instance('page-body')->add(array(
    'template' => 'CRM/Contributionrecur/ContributionRecur.tpl',
  ));
  CRM_Core_Resources::singleton()->addScriptFile('ca.civicrm.contributionrecur', 'js/subscription_view.js');
}

function _contributionrecur_get_iats_extra($recur) {
  if (empty($recur['invoice_id'])) {
    return;
  }
  $params = array(1 => array('civicrm_iats_request_log', 'String'));
  $dao = CRM_Core_DAO::executeQuery("SHOW TABLES LIKE %1", $params);
  if (!$dao->fetch()) {
    return;
  }
  $params[2] = array($recur['invoice_id'],'String');
  $dao = CRM_Core_DAO::executeQuery("SELECT * FROM %1 WHERE invoice_num = %2", $params);
  if (!$dao->fetch()) {
    return;
  }
  $extra = array('cc' => $dao->cc);
  return $extra;
}
