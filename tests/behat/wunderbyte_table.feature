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
      | teacher1 | Teacher   | 1        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | course | name       | intro                  | bookingmanager | eventtype | Default view for booking options | Activate e-mails (confirmations, notifications and more) | Booking option name  |
      | booking  | C1     | My booking | My booking description | teacher1       | Webinar   | All bookings                     | Yes                                                      | New option - Webinar |

  @javascript
  Scenario: Display single table
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    ## Then "2" row "username" column of "Users" table should contain "admin"
    Then the following should exist in the "Users" table:
      | username | firstname | email                |
      | admin    | Admin     | moodle@example.com   |
      | teacher1 | Teacher   | teacher1@example.com |
      | user1    | Username  | user1@example.com    |

  @javascript
  Scenario: Display few tables per multiple tabs
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Users"
    Then the following should exist in the "Users" table:
      | username | firstname | email              |
      | admin    | Admin     | moodle@example.com |
      | user1    | Username  | user1@example.com  |
    And I follow "Course"
    And I wait "1" seconds
    Then the following should exist in the "Course" table:
      | Full Name            | Short Name           |
      | Acceptance test site | Acceptance test site |
      | Course 1             | C1                   |
    And I follow "Course_Modules"
    And I wait "1" seconds
    Then the following should exist in the "Course_Modules" table:
      | module |
      | 5      |

  @javascript
  Scenario: Navigation - switch to the next page
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Users"
    And "//nav[@aria-label='Page']" "xpath_element" should exist
    And I click on "2" "link" in the "ul.pagination" "css_element"
    And I should not see "guest"
    And I click on "1" "link" in the "ul.pagination" "css_element"
    And I should see "guest"

  @javascript
  Scenario: Navigation - set per page items count
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Users"
    Then I should see "15 of 15 records found" in the ".Users .wb-records-count-label" "css_element"
    And the field "selectrowsperpage-Users" matches value "Show 10 rows"
    And "//*[contains(@id, 'home')]//nav[@aria-label='Page']" "xpath_element" should exist
    And I set the field "selectrowsperpage-Users" to "Show 20 rows"
    And I wait "1" seconds
    And "//*[contains(@id, 'home')]//nav[@aria-label='Page']" "xpath_element" should not exist
    And the field "selectrowsperpage-Users" matches value "Show 20 rows"
