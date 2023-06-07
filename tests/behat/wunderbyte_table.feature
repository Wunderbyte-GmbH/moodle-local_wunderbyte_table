@local @local_wunderbyte_table
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

  @javascript
  Scenario: WB_Table: Display single table
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    ## Then "2" row "username" column of "Users" table should contain "admin"
    Then the following should exist in the "Users" table:
      | Username | First name | Email address        |
      | admin    | Admin      | moodle@example.com   |
      | teacher1 | Teacher    | teacher1@example.com |
      | user1    | Username   | user1@example.com    |

  @javascript
  Scenario: WB_Table: Display few tables per multiple tabs
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Users"
    Then the following should exist in the "Users" table:
      | Username | First name | Email address      |
      | admin    | Admin      | moodle@example.com |
      | user1    | Username   | user1@example.com  |
    And I follow "Course"
    And I wait "1" seconds
    Then the following should exist in the "Course" table:
      | Full name            | Short name           |
      | Acceptance test site | Acceptance test site |
      | Course 1             | C1                   |
    And I follow "Course_Modules"
    And I wait "1" seconds
    Then the following should exist in the "Course_Modules" table:
      | Module |
      | 5      |

  @javascript
  Scenario: WB_Table navigation: switch to the next page
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Users"
    And "//nav[@aria-label='Page']" "xpath_element" should exist
    And I click on "2" "link" in the "ul.pagination" "css_element"
    And I should not see "guest"
    And I click on "1" "link" in the "ul.pagination" "css_element"
    And I should see "guest"

  @javascript
  Scenario: WB_Table navigation: set per page items count
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Users"
    Then I should see "24 of 24 records found" in the ".Users .wb-records-count-label" "css_element"
    And the field "selectrowsperpage-Users" matches value "Show 10 rows"
    And "//*[contains(@id, 'Users')]//nav[@aria-label='Page']" "xpath_element" should exist
    And I set the field "selectrowsperpage-Users" to "Show 30 rows"
    And I wait "1" seconds
    And "//*[contains(@id, 'Users')]//nav[@aria-label='Page']" "xpath_element" should not exist
    And the field "selectrowsperpage-Users" matches value "Show 30 rows"

  @javascript
  Scenario: WB_Table navigation: infinite scroll
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Users_InfiniteScroll"
    And I should see "user12" in the "#Users_InfiniteScroll_r7" "css_element"
    And "//*[contains(@id, 'Users_InfiniteScroll')]//tr[@id, 'Users_InfiniteScroll_r16']" "xpath_element" should not exist
    And I click on "TriggersNoModal" "link" in the "#Users_InfiniteScroll_r7" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"
    And I should see "user19" in the "#Users_InfiniteScroll_r14" "css_element"
    And "//*[contains(@id, 'Users_InfiniteScroll')]//tr[@id, 'Users_InfiniteScroll_r16']" "xpath_element" should not exist
    And I click on "TriggersNoModal" "link" in the "#Users_InfiniteScroll_r14" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"
    And I should see "user20" in the "#Users_InfiniteScroll_r16" "css_element"
