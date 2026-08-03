<?php

/**
 * @file
 * Membership Payer hooks.
 */

require_once 'membership_payer.civix.php';

use Civi\Api4\Membership;
use CRM_MembershipPayer_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 */
function membership_payer_civicrm_config(&$config): void {
  _membership_payer_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 */
function membership_payer_civicrm_xmlMenu(&$files): void {
  $files[] = __DIR__ . '/xml/Menu/membership_payer.xml';
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 */
function membership_payer_civicrm_alterSettingsFolders(&$metaDataFolders = null): void {
  $metaDataFolders[] = __DIR__ . '/settings';
}

/**
 * Implements hook_civicrm_navigationMenu().
 */
function membership_payer_civicrm_navigationMenu(&$menu): void {
  _membership_payer_civix_insert_navigation_menu($menu, 'Administer/CiviContribute', [
    'label' => E::ts('Membership Payer'),
    'name' => 'membership_payer_setting',
    'url' => 'civicrm/admin/setting/membership-payer?reset=1',
    'permission' => 'access CiviContribute,administer CiviCRM',
    'operator' => 'AND',
    'separator' => 0,
  ]);
  _membership_payer_civix_navigationMenu($menu);
}

/**
 * Implements hook_civicrm_alterMenu().
 */
function membership_payer_civicrm_alterMenu(&$items): void {
  $path = 'civicrm/admin/setting/membership-payer';
  if (!isset($items[$path])) {
    return;
  }

  $items[$path]['title'] = E::ts('Membership Payer');
  $items[$path]['desc'] = E::ts('Configure legacy contribution pages where an organisation pays for an individual membership.');
}

/**
 * Limits the extension to explicitly configured legacy contribution pages.
 */
function membership_payer_is_enabled_for_form($form): bool {
  $pageID = (int) ($form->_id ?? 0);
  $enabledPageIDs = \Civi::settings()->get('membership_payer_contribution_page_ids') ?: [];
  if (!is_array($enabledPageIDs)) {
    $enabledPageIDs = preg_split('/\s*,\s*/', (string) $enabledPageIDs);
  }
  $enabledPageIDs = array_filter(array_map('intval', $enabledPageIDs));
  return $pageID && in_array($pageID, $enabledPageIDs, true);
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * Core has already created the organisation, the payer contribution and the
 * employee relationship at this point. Move only new memberships to the
 * individual recorded by the on-behalf profile. The hook is invoked by the
 * contribution form before it redirects to a hosted payment processor or shows
 * the thank-you page.
 */
function membership_payer_civicrm_postProcess($formName, &$form): void {
  $allowedForms = ['CRM_Contribute_Form_Contribution_Confirm', 'CRM_Contribute_Form_Contribution_Main'];
  if (!in_array($formName, $allowedForms, true) || !membership_payer_is_enabled_for_form($form)) {
    return;
  }

  $memberID = (int) ($form->_params['onbehalf_contact_id'] ?? 0);

  $membershipIDs = (array) ($form->_params['createdMembershipIDs'] ?? []);
  if (empty($membershipIDs) && !empty($form->_membershipId)) {
    $membershipIDs = [$form->_membershipId];
  }
  if (empty($membershipIDs) && !empty($form->_params['membershipID'])) {
    $membershipIDs = [$form->_params['membershipID']];
  }

  $membershipIDs = array_filter(array_map('intval', $membershipIDs));

  if (!$memberID || !$membershipIDs) {
    return;
  }

  Membership::update(false)
    ->addWhere('id', 'IN', $membershipIDs)
    ->addValue('contact_id', $memberID)
    ->execute();
}
