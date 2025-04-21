@local @local_wunderbyte_table @local_wunderbyte_table_navigation
Feature: Baisc functionality of wunderbyte_table works as expected

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
    And I clean wbtable cache
    And I change viewport size to "1600x1200"

  @javascript
  Scenario: WB_Table: Display single table
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    ## Then "2" row "username" column of "Demo table 1" table should contain "admin"
    Then the following should exist in the "demotable_1" table:
      | Username | First name | Email address        |
      | admin    | Admin      | moodle@example.com   |
      | teacher1 | Teacher    | teacher1@example.com |
      | user1    | Username   | user1@example.com    |

  @javascript
  Scenario: WB_Table: Display few tables per multiple tabs
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    ## Demo table 1 - users
    And I follow "Demo table 1"
    Then the following should exist in the "demotable_1" table:
      | Username | First name | Email address      |
      | admin    | Admin      | moodle@example.com |
      | user1    | Username   | user1@example.com  |
    ## Demo table 2 - courses
    And I follow "Demo table 2"
    And I wait "1" seconds
    Then the following should exist in the "demotable_2" table:
      | Full name            | Short name           |
      | Acceptance test site | Acceptance test site |
      | Course 1             | C1                   |
    ## Demo table 3 - course modules
    And I follow "Demo table 3"
    And I wait "1" seconds
    And I should see "1 of 1 records found" in the ".wunderbyteTableClass.demotable_3" "css_element"
    And I should see "TriggersModal" in the "#demotable_3_r1" "css_element"

  @javascript
  Scenario: WB_Table navigation: switch to the next page
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    And "//nav[@aria-label='Page']" "xpath_element" should exist
    And I click on "2" "text" in the "ul.pagination" "css_element"
    And I should not see "guest"
    And I click on "1" "text" in the "ul.pagination" "css_element"
    And I should see "guest"

  @javascript
  Scenario: WB_Table navigation: set per page items count
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    Then I should see "24 of 24 records found" in the ".demotable_1 .wb-records-count-label" "css_element"
    And the field "selectrowsperpage-demotable_1" matches value "Show 10 rows"
    And "//*[contains(@class, 'demotable_1')]//nav[@aria-label='Page']" "xpath_element" should exist
    And I set the field "selectrowsperpage-demotable_1" to "Show 30 rows"
    And I wait "1" seconds
    And "//*[contains(@class, 'demotable_1')]//nav[@aria-label='Page']" "xpath_element" should not exist
    And the field "selectrowsperpage-demotable_1" matches value "Show 30 rows"

  @javascript
  Scenario: WB_Table navigation: infinite scroll
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    ## Demo table 4 - Users_InfiniteScroll
    And I follow "Demo table 4"
    # Ensure of available / not yet loaded records:
    And I should see "user12" in the "#demotable_4_r7" "css_element"
    And "//*[contains(@id, 'demotable_4')]//tr[@id, 'demotable_4_r16']" "xpath_element" should not exist
    ## Call pagedown twice to ensure actual bottom of page will be reached.
    And I press the pagedown key
    And I press the pagedown key
    And I wait "1" seconds
    And I should see "user19" in the "#demotable_4_r14" "css_element"
    ## Call pagedown twice to ensure actual bottom of page will be reached.
    And I press the pagedown key
    And I press the pagedown key
    # Ensure of available / not yet loaded records:
    And "//*[contains(@id, 'demotable_4')]//tr[@id, 'demotable_4_r16']" "xpath_element" should not exist
    And I wait "1" seconds
    And I should see "user20" in the "#demotable_4_r16" "css_element"

  @javascript
  Scenario: WB_Table navigation: switch view templates
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    ## Verify default - list viwe
    And the field "wbtabletemplateswitcher" matches value "List view"
    Then "//table[@id='demotable_1']" "xpath_element" should exist
    And ".wunderbyte-table-grid" "css_element" should not exist
    ## Switch to the Card view and verify
    And I set the field "wbtabletemplateswitcher" to "Cards view"
    And ".wunderbyte-table-grid" "css_element" should exist
    And "//table[@id='demotable_1']" "xpath_element" should not exist
    ## Reload page and verify Card view
    And I reload the page
    And ".wunderbyte-table-grid" "css_element" should exist
    And "//table[@id='demotable_1']" "xpath_element" should not exist
