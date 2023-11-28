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
    ## Then "2" row "username" column of "Demo table 1" table should contain "admin"
    Then the following should exist in the "fa5e95f7721ad449d1cdd30f461d1cec" table:
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
    Then the following should exist in the "fa5e95f7721ad449d1cdd30f461d1cec" table:
      | Username | First name | Email address      |
      | admin    | Admin      | moodle@example.com |
      | user1    | Username   | user1@example.com  |
    ## Demo table 2 - courses
    And I follow "Demo table 2"
    And I wait "1" seconds
    Then the following should exist in the "d82aa03a37e6ab3d58369289ffadc665" table:
      | Full name            | Short name           |
      | Acceptance test site | Acceptance test site |
      | Course 1             | C1                   |
    ## Demo table 3 - course modules
    And I follow "Demo table 3"
    And I wait "1" seconds
    Then the following should exist in the "7c4920728afa854f9975943ccf1c8960" table:
      | Module |
      | 5      |

  @javascript
  Scenario: WB_Table navigation: switch to the next page
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    And "//nav[@aria-label='Page']" "xpath_element" should exist
    And I click on "2" "link" in the "ul.pagination" "css_element"
    And I should not see "guest"
    And I click on "1" "link" in the "ul.pagination" "css_element"
    And I should see "guest"

  @javascript
  Scenario: WB_Table navigation: set per page items count
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    Then I should see "24 of 24 records found" in the ".fa5e95f7721ad449d1cdd30f461d1cec .wb-records-count-label" "css_element"
    And the field "selectrowsperpage-fa5e95f7721ad449d1cdd30f461d1cec" matches value "Show 10 rows"
    And "//*[contains(@class, 'fa5e95f7721ad449d1cdd30f461d1cec')]//nav[@aria-label='Page']" "xpath_element" should exist
    And I set the field "selectrowsperpage-fa5e95f7721ad449d1cdd30f461d1cec" to "Show 30 rows"
    And I wait "1" seconds
    And "//*[contains(@class, 'fa5e95f7721ad449d1cdd30f461d1cec')]//nav[@aria-label='Page']" "xpath_element" should not exist
    And the field "selectrowsperpage-fa5e95f7721ad449d1cdd30f461d1cec" matches value "Show 30 rows"

  @javascript
  Scenario: WB_Table navigation: infinite scroll
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    ## Demo table 4 - Users_InfiniteScroll
    And I follow "Demo table 4"
    # Ensure of available / not yet loaded records:
    And I should see "user12" in the "#7ef0620c51db513cad089dcd596522bc_r7" "css_element"
    And "//*[contains(@id, '7ef0620c51db513cad089dcd596522bc')]//tr[@id, '7ef0620c51db513cad089dcd596522bc_r16']" "xpath_element" should not exist
    ## Call pagedown twice to ensure actual bottom of page will be reached.
    And I press the pagedown key
    And I press the pagedown key
    And I wait "1" seconds
    And I should see "user19" in the "#7ef0620c51db513cad089dcd596522bc_r14" "css_element"
    ## Call pagedown twice to ensure actual bottom of page will be reached.
    And I press the pagedown key
    And I press the pagedown key
    # Ensure of available / not yet loaded records:
    And "//*[contains(@id, '7ef0620c51db513cad089dcd596522bc')]//tr[@id, '7ef0620c51db513cad089dcd596522bc_r16']" "xpath_element" should not exist
    And I wait "1" seconds
    And I should see "user20" in the "#7ef0620c51db513cad089dcd596522bc_r16" "css_element"
