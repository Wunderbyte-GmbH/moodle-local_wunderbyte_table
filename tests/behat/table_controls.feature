@local @local_wunderbyte_table
Feature: Table controls functionality of wunderbyte_table works as expected

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | Username  | 1        |
      | user2    | Username  | 2        |
      | user3    | Username  | 3        |
      | user4    | Username  | 4        |
      | user5    | Username  | 5        |
      | user6    | Username  | 6        |
      | user7    | Username  | 7        |
      | user8    | Username  | 8        |
      | user9    | Username  | 9        |
      | user10   | Username  | 10       |
      | user11   | Username  | 11       |
      | user12   | Username  | 12       |
      | user13   | Username  | 13       |
      | user14   | Username  | 14       |
      | user15   | Username  | 15       |
      | user16   | Username  | 16       |
      | user17   | Username  | 17       |
      | user18   | Username  | 18       |
      | user19   | Username  | 19       |
      | user20   | Username  | 20       |
      | user21   | Username  | 21       |
      | teacher1 | Teacher   | 1        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name       | intro      | course | idnumber |
      | page     | PageName1  | PageDesc1  | C1     | PAGE1    |
    And I change viewport size to "1600x3000"
    And I clean wbtable cache

  @javascript
  Scenario: Press TriggersNoModal NoSelection buttons for entire table
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    Then I should see "admin" in the "#demotable_1_r1" "css_element"
    And I click on "NoModal, MultipleCall, NoSelection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"
    And I wait "1" seconds
    And I click on "NoModal, SingleCall, NoSelection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"

  @javascript
  Scenario: Press TriggersNoModal Selection buttons for entire table
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    Then I should see "admin" in the "#demotable_1_r1" "css_element"
    And I click on "NoModal, MultipleCall, Selection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I wait "1" seconds
    And I should see "No checkbox checked" in the "#user-notifications" "css_element"
    ## Fix for "Element is not clickable ... because another element .... obscures it"
    And I follow "Demo table 1"
    And I set the field with xpath "//*[contains(@id, 'demotable_1_r2')]//*[contains(@name, 'row-demotable_1-')]" to "checked"
    And I click on "NoModal, MultipleCall, Selection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"
    And I wait "1" seconds
    ## Fix for "Element is not clickable ... because another element .... obscures it"
    And I follow "Demo table 1"
    And I set the field with xpath "//*[contains(@id, 'demotable_1_r2')]//*[contains(@name, 'row-demotable_1-')]" to ""
    And I click on "NoModal, SingleCall, Selection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I wait "1" seconds
    And I should see "No checkbox checked" in the "#user-notifications" "css_element"
    ## Fix for "Element is not clickable ... because another element .... obscures it"
    And I follow "Demo table 1"
    And I set the field with xpath "//*[contains(@id, 'demotable_1_r3')]//*[contains(@name, 'row-demotable_1-')]" to "checked"
    And I click on "NoModal, SingleCall, Selection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"

  @javascript
  Scenario: Press TriggersModal NoSelection buttons for entire table
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    Then I should see "admin" in the "#demotable_1_r1" "css_element"
    And I click on "+Modal, MultipleCall, NoSelection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I should see "Action will be applied!" in the ".show .modal-content" "css_element"
    And I click on ".show .modal-dialog .modal-footer .btn-primary" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"
    And I wait "1" seconds
    And I click on "+Modal, SingleCall, NoSelection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I should see "You are about to add a row" in the ".show .modal-content" "css_element"
    And I click on ".show .modal-dialog .modal-footer .btn-primary" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"

  @javascript
  Scenario: Press TriggersModal Selection buttons for entire table
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    Then I should see "guest" in the "#demotable_1_r2" "css_element"
    And I click on "+Modal, MultipleCall, Selection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I wait "1" seconds
    And I should see "No checkbox checked" in the "#user-notifications" "css_element"
    ## Fix for "Element is not clickable ... because another element .... obscures it"
    And I follow "Demo table 1"
    And I set the field with xpath "//*[contains(@id, 'demotable_1_r2')]//*[contains(@name, 'row-demotable_1-')]" to "checked"
    And I click on "+Modal, MultipleCall, Selection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I should see "You are about to submit this data:" in the ".show .modal-content" "css_element"
    And I should see "Guest user" in the ".show .modal-content" "css_element"
    And I click on ".show .modal-dialog .modal-footer .btn-primary" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"
    And I wait "1" seconds
    ## Fix for "Element is not clickable ... because another element .... obscures it"
    And I follow "Demo table 1"
    And I set the field with xpath "//*[contains(@id, 'demotable_1_r2')]//*[contains(@name, 'row-demotable_1-')]" to ""
    And I click on "+Modal, SingleCall, Selection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I wait "1" seconds
    And I should see "No checkbox checked" in the "#user-notifications" "css_element"
    ## Fix for "Element is not clickable ... because another element .... obscures it"
    And I follow "Demo table 1"
    And I set the field with xpath "//*[contains(@id, 'demotable_1_r3')]//*[contains(@name, 'row-demotable_1-')]" to "checked"
    And I click on "+Modal, SingleCall, Selection" "link" in the ".wunderbyteTableClass.demotable_1" "css_element"
    And I should see "You are about to submit this data:" in the ".show .modal-content" "css_element"
    And I should see "Teacher" in the ".show .modal-content" "css_element"
    And I click on ".show .modal-dialog .modal-footer .btn-primary" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"
