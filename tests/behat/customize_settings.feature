@local @local_wunderbyte_table
Feature: As admin I want to ensure that customization of the wunderbyte_table settings works as expected

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

  @javascript
  Scenario: WB_table: Verify filter settings working on download
    Given I log in as "admin"
    And I set the following administration settings values:
      | allowedittable | 1 |
    ## And I press "Save changes"
    And I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    ## Enable "filter on download", download table and verify file size
    And I click on ".wb_edit_button" "css_element" in the ".demotable_1" "css_element"
    And I wait "1" seconds
    And I set the field "Apply filter on download" in the ".modal-body" "css_element" to "checked"
    And I press "Save changes"
    And I wait until the page is ready
    And I click on "[aria-controls=\"id_collapse_username\"]" "css_element"
    And I should see "admin" in the "#id_collapse_username" "css_element"
    And I set the field "teacher1" in the "#id_collapse_username" "css_element" to "checked"
    And I wait "1" seconds
    And I should see "teacher1" in the "#demotable_1_r1" "css_element"
    And I should see "1 of 24 records found" in the ".wb-records-count-label" "css_element"
    And I should see "1 filter(s) on: Username" in the ".wb-records-count-label" "css_element"
    ## Does not working for JS-inititiated download
    ## Then following "/local/wunderbyte_table/download.php?wbtsearch=teacher" should download "371" bytes
    ## Verify current state than disable "filter on download", download table again and verify file size
    And I click on ".wb_edit_button" "css_element" in the ".demotable_1" "css_element"
    And I wait "1" seconds
    And the field "Apply filter on download" matches value "checked"
    And I set the field "Apply filter on download" in the ".modal-body" "css_element" to ""
    And I press "Save changes"
    And I wait until the page is ready
    And I click on "[aria-controls=\"id_collapse_username\"]" "css_element"
    And I should see "admin" in the "#id_collapse_username" "css_element"
    And I set the field "teacher1" in the "#id_collapse_username" "css_element" to "checked"
    And I wait "1" seconds
    And I should see "teacher1" in the "#demotable_1_r1" "css_element"
    And I should see "1 of 24 records found" in the ".wb-records-count-label" "css_element"
    And I should see "1 filter(s) on: Username" in the ".wb-records-count-label" "css_element"
    ## Does not working for JS-inititiated download
    ## Then following "/local/wunderbyte_table/download.php?wbtsearch=teacher" should download "7370" bytes
    And I set the following administration settings values:
      | allowedittable |  |
    And I clean wbtable cache
    And I log out

  @javascript
  Scenario: WB_table settings: control presence of strings on all settings pages
    Given I log in as "admin"
    And I visit "/admin/category.php?category=local_wunderbyte_table"
    And I wait "1" seconds
    And I visit "/cache/admin.php"
    And I wait "1" seconds
    And I visit "/admin/webservice/testclient.php"
    And I wait "1" seconds
    And I visit "/admin/tool/behat/index.php"
    And I set the field "component" to "behat_local_wunderbyte_table"
    And I press "Filter"
    And I should see "Clean wbtable cache" in the ".steps-definitions .step" "css_element"
    ## Recommended admin pages
    And I log out
