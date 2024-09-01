@local @local_wunderbyte_table
Feature: Filtering functionality of wunderbyte_table works as expected

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

  @javascript
  Scenario: Filter tables on different tabs using input field
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    And I set the field "search-demotable_1" to "teacher"
    And I wait "1" seconds
    And I should see "teacher1" in the "#demotable_1_r1" "css_element"
    And I set the field "search-demotable_1" to "admin"
    And I wait "1" seconds
    And I should see "admin" in the "#demotable_1_r1" "css_element"
    And I set the field "search-demotable_1" to "guest"
    And I wait "1" seconds
    And I should see "guest" in the "#demotable_1_r1" "css_element"
    And I follow "Demo table 2"
    And I set the field "search-demotable_2" to "course"
    And I wait "1" seconds
    And I should see "Course 1" in the "#demotable_2_r1" "css_element"
    And I set the field "search-demotable_2" to "site"
    And I wait "1" seconds
    And I should see "Acceptance test site" in the "#demotable_2_r1" "css_element"

  @javascript
  Scenario: Filter users table by username via sidebar filter controls
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    And I should see "guest" in the "#demotable_1_r2" "css_element"
    And I click on "[aria-controls=\"id_collapse_username\"]" "css_element"
    And I should see "admin" in the "#id_collapse_username" "css_element"
    And I set the field "admin" in the "#id_collapse_username" "css_element" to "checked"
    And I wait "1" seconds
    And I should see "admin" in the "#demotable_1_r1" "css_element"
    And "//*[contains(@id, 'demotable_1')]//tr[@id, 'demotable_1_r2']" "xpath_element" should not exist
    And I set the field "guest" in the "#id_collapse_username" "css_element" to "checked"
    And I wait "1" seconds
    And I should see "guest" in the "#demotable_1_r2" "css_element"
    ## And "//*[contains(@id, 'Users')]//tr[@id, 'Users_r3']" "xpath_element" should not exist
    And I should see "2 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I set the field "admin" in the "#id_collapse_username" "css_element" to ""
    And I wait "1" seconds
    And I should see "guest" in the "#demotable_1_r1" "css_element"
    And "//*[contains(@id, 'demotable_1')]//tr[@id, 'demotable_1_r2']" "xpath_element" should not exist
    And I set the field "guest" in the "#id_collapse_username" "css_element" to ""
    And I wait "1" seconds
    And I should see "24 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"

  @javascript
  Scenario: Filter users table by username and reset filter
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    And I click on "[aria-controls=\"id_collapse_username\"]" "css_element"
    And I set the field "admin" in the "#id_collapse_username" "css_element" to "checked"
    And I set the field "user15" in the "#id_collapse_username" "css_element" to "checked"
    And I wait until the page is ready
    And I should see "admin" in the "#demotable_1_r1" "css_element"
    And I should see "user15" in the "#demotable_1_r2" "css_element"
    And I should see "2 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I should see "2 filter(s) on: Username" in the ".tab-pane.active .wb-records-count-label" "css_element"
    ## And I press "Show all records"
    And I click on "Show all records" "text" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I wait until the page is ready
    And I should see "24 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"

  @javascript
  Scenario: Search username in sidebar filter controls and filter by it
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    And I click on "[aria-controls=\"id_collapse_username\"]" "css_element"
    Then "//input[@name='filtersearch-username']" "xpath_element" should exist
    And I set the field "filtersearch-username" in the "#id_collapse_username" "css_element" to "user15"
    And I should not see "user14" in the "#id_collapse_username" "css_element"
    And I should see "user15" in the "#id_collapse_username" "css_element"
    And I should not see "user16" in the "#id_collapse_username" "css_element"
    And I set the field "user15" in the "#id_collapse_username" "css_element" to "checked"
    And I wait "1" seconds
    And I should see "user15" in the "#demotable_1_r1" "css_element"
    And I should see "1 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    ## Remove filter and search
    And I set the field "user15" in the "#id_collapse_username" "css_element" to ""
    And I set the field "filtersearch-username" in the "#id_collapse_username" "css_element" to ""
    And I wait "1" seconds
    And I should see "user14" in the "#id_collapse_username" "css_element"
    And I should see "user15" in the "#id_collapse_username" "css_element"
    And I should see "user16" in the "#id_collapse_username" "css_element"
    And I should see "24 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"

  @javascript
  Scenario: Filter multiple tables consequently using sidebar filter controls
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    ## Filter panel being hidden by default on the Infinite Scroll tab
    And I follow "Demo table 4"
    And I click on ".asidecollapse-demotable_4" "css_element"
    And I should see "Teacher" in the "#demotable_4_r3" "css_element"
    ##And I press "filterbtn-demotable_4"
    And I click on ".tab-pane.active [aria-controls=\"id_collapse_firstname\"]" "css_element"
    And I should see "Teacher" in the ".tab-pane.active #id_collapse_firstname" "css_element"
    And I set the field "Teacher" in the ".tab-pane.active #id_collapse_firstname" "css_element" to "checked"
    And I wait until the page is ready
    And I should see "Teacher" in the "#demotable_4_r1" "css_element"
    And "//*[contains(@id, 'demotable_4')]//tr[@id, 'demotable_4_r2']" "xpath_element" should not exist
    ## Filter panel being hidden by default on the Users tab
    And I follow "Demo table 1"
    And I should see "guest" in the "#demotable_1_r2" "css_element"
    And I click on ".tab-pane.active [aria-controls=\"id_collapse_username\"]" "css_element"
    And I should see "admin" in the ".tab-pane.active #id_collapse_username" "css_element"
    And I set the field "admin" in the ".tab-pane.active #id_collapse_username" "css_element" to "checked"
    And I wait "1" seconds
    And I should see "admin" in the "#demotable_1_r1" "css_element"
    And "//*[contains(@id, 'demotable_1')]//tr[@id, 'demotable_1_r2']" "xpath_element" should not exist
    ## Filter panel being hidden by default on the Course tab
    And I follow "Demo table 2"
    And I click on ".asidecollapse-demotable_2" "css_element"
    And I should see "Course 1" in the "#demotable_2_r2" "css_element"
    And I click on ".tab-pane.active [aria-controls=\"id_collapse_fullname\"]" "css_element"
    And I should see "Course 1" in the ".tab-pane.active #id_collapse_fullname" "css_element"
    And I set the field "Course 1" in the ".tab-pane.active #id_collapse_fullname" "css_element" to "checked"
    And I wait "1" seconds
    And I should see "Course 1" in the "#demotable_2_r1" "css_element"
    And "//*[contains(@id, 'demotable_2')]//tr[@id, 'demotable_2_r2']" "xpath_element" should not exist
