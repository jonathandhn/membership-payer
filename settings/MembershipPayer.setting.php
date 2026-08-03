<?php

use CRM_MembershipPayer_ExtensionUtil as E;

return [
  'membership_payer_contribution_page_ids' => [
    'name' => 'membership_payer_contribution_page_ids',
    'type' => 'Array',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => [
      'multiple' => 1,
      'class' => 'huge crm-select2',
    ],
    'default' => [],
    'group' => 'membership_payer',
    'group_name' => 'Membership Payer',
    'title' => E::ts('Contribution pages with organisation-paid membership'),
    'description' => E::ts('Select the legacy contribution pages where an organisation pays while the individual receives the membership. Other pages retain their default behavior.'),
    'is_domain' => 1,
    'is_contact' => 0,
    'pseudoconstant' => [
      'callback' => 'CRM_Contribute_PseudoConstant::contributionPage',
    ],
    'settings_pages' => [
      'contribute' => ['weight' => 90],
      'membership-payer' => ['weight' => 10],
    ],
  ],
];
