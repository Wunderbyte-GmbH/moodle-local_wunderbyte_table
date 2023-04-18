@local @local_wunderbyte_table
Feature: Sorting functionality of wunderbyte_table works as expected

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
    Then the following should exist in the "Course" table:
      | Full Name            | Short Name           |
      | Acceptance test site | Acceptance test site |
      | Course 1             | C1                   |
    And I follow "Course_Modules"
    Then the following should exist in the "Course_Modules" table:
      | course | module |
      | 362000 | 5      |

  @javascript
  Scenario: Sort table using select field
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Users"
    And I click on "#home a.changesortorder" "css_element"
    And I click on "#home a.changesortorder" "css_element"
    And I set the field "Sort by..." to "id"
    And I should see "guest" in the "#Users_r1" "css_element"
    And I click on "#home a.changesortorder" "css_element"
    And I should see "teacher1" in the "#Users_r1" "css_element"
    And I set the field "#home select.sortcolumn" to "username"
    And I should see "user9" in the "#Users_r1" "css_element"
    And I click on "#home a.changesortorder" "css_element"
    And I should see "admin" in the "#Users_r1" "css_element"
    And I set the field "#home select.sortcolumn" to "lastname"
    And I should see "guest" in the "#Users_r1" "css_element"
    And I click on "#home a.changesortorder" "css_element"
    And I should see "admin" in the "#Users_r1" "css_element"
    And I set the field "#home select.sortcolumn" to "firstname"
    And I should see "user9" in the "#Users_r1" "css_element"
    And I click on "#home a.changesortorder" "css_element"
    And I should see "admin" in the "#Users_r1" "css_element"

  @javascript
  Scenario: Sort table with column id
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I should see "id" in the "th.id.wb-table-column" "css_element"
    ## And I click on "//*[@id='Users']/thead/tr/th[2]" "xpath_element"
    And I click on "th.id.wb-table-column" "css_element"
    And I should see "teacher1" in the "#Users_r1" "css_element"
    And I click on "th.id.wb-table-column.asc" "css_element"
    And I should see "guest" in the "#Users_r1" "css_element"
    ## TODO: when clicking column header in table (for sorting) - sync value in the elect class="sortcolumn" as well

  @javascript
  Scenario: Sort table with column username
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I should see "username" in the "th.username.wb-table-column" "css_element"
    ## And I click on "//*[@id='Users']/thead/tr/th[3]" "xpath_element"
    And I click on "th.username.wb-table-column" "css_element"
    And I should see "user9" in the "#Users_r1" "css_element"
    And I click on "th.username.wb-table-column.asc" "css_element"
    And I should see "admin" in the "#Users_r1" "css_element"
    ## TODO: when clicking column header in table (for sorting) - sync value in the elect class="sortcolumn" as well