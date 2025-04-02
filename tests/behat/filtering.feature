@local @local_wunderbyte_table
Feature: Filtering functionality of wunderbyte_table works as expected

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | department |
      | user1    | Username  | 1        | 1,2        |
      | user2    | Username  | 2        | 2          |
      | user3    | Username  | 3        | 3,4        |
      | user4    | Username  | 4        | 1,2        |
      | user5    | Username  | 5        | ,1         |
      | user6    | Username  | 6        | 1,4        |
      | user7    | Username  | 7        | 2          |
      | user8    | Username  | 8        | 5          |
      | user9    | Username  | 9        | 3          |
      | user10   | Username  | 10       | 2          |
      | user11   | Username  | 11       | 2          |
      | user12   | Username  | 12       | 1,2        |
      | user13   | Username  | 13       | 5          |
      | user14   | Username  | 14       | 7          |
      | user15   | Username  | 15       | 6,7        |
      | user16   | Username  | 16       | 4,5,6,7,8  |
      | user17   | Username  | 17       | 1          |
      | user18   | Username  | 18       | 1          |
      | user19   | Username  | 19       | 1          |
      | user20   | Username  | 20       | 1          |
      | user21   | Username  | 21       | 1          |
      | teacher1 | Teacher   | 1        | 1          |
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
  Scenario: WB_Table: Filter tables on different tabs using input field
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
    And I clean wbtable cache

  @javascript
  Scenario: WB_Table: Filter users table by username via sidebar filter controls
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
    And I clean wbtable cache

  @javascript
  Scenario: WB_Table: Filter users table by username and reset filter
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    And I click on "[aria-controls=\"id_collapse_username\"]" "css_element"
    And I set the field "admin" in the "#id_collapse_username" "css_element" to "checked"
    And I wait "1" seconds
    And I set the field "user15" in the "#id_collapse_username" "css_element" to "checked"
    ## And I wait until the page is ready
    And I wait "1" seconds
    And I should see "admin" in the "#demotable_1_r1" "css_element"
    And I should see "user15" in the "#demotable_1_r2" "css_element"
    And I should see "2 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I should see "2 filter(s) on: Username" in the ".tab-pane.active .wb-records-count-label" "css_element"
    ## And I press "Show all records"
    And I click on "Show all records" "text" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I wait until the page is ready
    And I should see "24 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I clean wbtable cache

  @javascript
  Scenario: WB_Table: Filter users table by department and reset filter
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    And I click on "[aria-controls=\"id_collapse_department\"]" "css_element"
    And I should see "first department (11)" in the "#id_collapse_department" "css_element"
    And I should see "3 (2)" in the "#id_collapse_department" "css_element"
    And I set the field "first department" in the "#id_collapse_department" "css_element" to "checked"
    And I wait "1" seconds
    And I should see "11 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    ## Hide filter - required for a new filter tool
    ## Workaround for case when hidden "search" "input" intercepts focus - so we cannot press "Teachers" "button"
    And I click on "//aside[contains(@class, 'wunderbyte_table_components')]" "xpath_element"
    And I click on "[aria-controls=\"id_collapse_username\"]" "css_element"
    And I set the field "user1" in the "#id_collapse_username" "css_element" to "checked"
    And I wait "1" seconds
    And I should see "user1" in the "#demotable_1_r1" "css_element"
    And I should see "1 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I should see "2 filter(s) on: Username, Department" in the ".tab-pane.active .wb-records-count-label" "css_element"
    ## And I press "Show all records"
    And I click on "Show all records" "text" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I wait "1" seconds
    And I should see "24 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I clean wbtable cache

  @javascript
  Scenario: WB_Table: Search username in sidebar filter controls and filter by it
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
    And I clean wbtable cache

  @javascript
  Scenario: WB_Table: Filter multiple tables consequently using sidebar filter controls
    Given the following "activity" exists:
      | activity       | url                 |
      | course         | C1                  |
      | idnumber       | URL1                |
      | name           | Moodle URL          |
      | externalurl    | https://moodle.org/ |
    And I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    ## Filter Table 4 - Infinite Scroll
    ## Filter panel being hidden by default on the Infinite Scroll tab
    And I follow "Demo table 4"
    And I click on ".asidecollapse-demotable_4" "css_element"
    And I should see "Teacher" in the "#demotable_4_r3" "css_element"
    And I click on ".tab-pane.active [aria-controls=\"id_collapse_firstname\"]" "css_element"
    And I should see "Teacher" in the ".tab-pane.active #id_collapse_firstname" "css_element"
    And I set the field "Teacher" in the ".tab-pane.active #id_collapse_firstname" "css_element" to "checked"
    And I wait until the page is ready
    And I should see "Teacher" in the "#demotable_4_r1" "css_element"
    And "//*[contains(@id, 'demotable_4')]//tr[@id, 'demotable_4_r2']" "xpath_element" should not exist
    ## Filter Table 1 - User
    ## Filter panel being hidden by default on the Users tab
    And I follow "Demo table 1"
    And I should see "guest" in the "#demotable_1_r2" "css_element"
    And I click on ".tab-pane.active [aria-controls=\"id_collapse_username\"]" "css_element"
    And I should see "admin" in the ".tab-pane.active #id_collapse_username" "css_element"
    And I set the field "admin" in the ".tab-pane.active #id_collapse_username" "css_element" to "checked"
    And I wait until the page is ready
    And I should see "admin" in the "#demotable_1_r1" "css_element"
    And "//*[contains(@id, 'demotable_1')]//tr[@id, 'demotable_1_r2']" "xpath_element" should not exist
    ## Filter Table 2 - Course
    ## Filter panel being hidden by default on the Course tab
    And I follow "Demo table 2"
    And I click on ".asidecollapse-demotable_2" "css_element"
    And I should see "Course 1" in the "#demotable_2_r2" "css_element"
    And I click on ".tab-pane.active [aria-controls=\"id_collapse_fullname\"]" "css_element"
    And I should see "Course 1" in the ".tab-pane.active #id_collapse_fullname" "css_element"
    And I set the field "Course 1" in the ".tab-pane.active #id_collapse_fullname" "css_element" to "checked"
    And I wait until the page is ready
    And I should see "Course 1" in the "#demotable_2_r1" "css_element"
    And "//*[contains(@id, 'demotable_2')]//tr[@id, 'demotable_2_r2']" "xpath_element" should not exist
    ## Filter Table 3 - Course Modules
    And I follow "Demo table 3"
    And I should see "PAGE1" in the "#demotable_3_r1" "css_element"
    And I click on ".tab-pane.active [aria-controls=\"id_collapse_idnumber\"]" "css_element"
    And I should see "URL1 (1)" in the ".tab-pane.active #id_collapse_idnumber" "css_element"
    And I set the field "URL1 (1)" in the ".tab-pane.active #id_collapse_idnumber" "css_element" to "checked"
    And I wait until the page is ready
    And I should see "URL1" in the "#demotable_3_r1" "css_element"
    And "//*[contains(@id, 'demotable_3')]//tr[@id, 'demotable_3_r2']" "xpath_element" should not exist
    And I should see "1 of 2 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I clean wbtable cache

  @javascript
  Scenario: WBTable: Filter by intrange in username using sidebar filter controls
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    ## Filter panel being hidden by default on the Demo Table 4 tab
    And I follow "Demo table 4"
    And I click on ".asidecollapse-demotable_4" "css_element"
    And I should see "Teacher" in the "#demotable_4_r3" "css_element"
    And I click on ".tab-pane.active [aria-controls=\"id_collapse_username\"]" "css_element"
    ##And I set the field "From" in the "#id_collapse_username" "css_element" to "1"
    ##And I set the field "To" in the "#id_collapse_username" "css_element" to "3"
    ##And I set the field "username" in the ".intrangeform input[data-intrangeelement=\"intrangeelement-checkbox\"]" "css_element" to "checked"
    And I set the field with xpath "//input[contains(@id, 'intrangefilter_intrange-start')]" to "1"
    And I set the field with xpath "//input[contains(@id, 'intrangefilter_intrange-end')]" to "3"
    And I set the field with xpath "//input[@data-intrangeelement='intrangeelement-checkbox']" to "checked"
    And I wait until the page is ready
    And I should see "4 of 24 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I should see "Teacher" in the "#demotable_4_r1" "css_element"
    And I should see "user3" in the "#demotable_4_r4" "css_element"
    And "//*[contains(@id, 'demotable_4')]//tr[@id, 'demotable_4_r5']" "xpath_element" should not exist
    ## And I set the field "To" in the "#id_collapse_username" "css_element" to "2"
    And I set the field with xpath "//input[contains(@id, 'intrangefilter_intrange-end')]" to "2"
    And I wait until the page is ready
    And I should see "user2" in the "#demotable_4_r3" "css_element"
    And "//*[contains(@id, 'demotable_4')]//tr[@id, 'demotable_4_r4']" "xpath_element" should not exist
    And I clean wbtable cache
