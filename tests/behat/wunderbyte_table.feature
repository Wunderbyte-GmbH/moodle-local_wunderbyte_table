@local @local_wunderbyte_table
Feature: Local table

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | Username  | 1        |
      | user2    | Username  | 2        |
      | teacher1  | Teacher   | 3        |
      | manager  | Manager   | 4        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | teacher1  | C1     | editingteacher |

  Scenario:
    Given I visit "/local/wunderbyte_table/test.php"
    When I log in as "teacher1"
    Then I am on "Testing table class" homepage
    And I set the field "downloadtype_download" to "Microsoft Excel (.xlsx)"
    And I press "Download"
    Then I should see "Testing table class"
    And I log out
    And I log in as "manager"
    When I am on "Course 1" course homepage
    And I visit "/local/wunderbyte_table/test.php"
    And I set the field "downloadtype_download" to "Comma separated values (.csv)"
    And I press "Download"
    Then I should see "Testing table class"
