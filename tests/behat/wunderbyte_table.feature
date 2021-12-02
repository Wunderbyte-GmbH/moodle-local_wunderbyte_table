@local @local_wunderbyte_table @javascript
Feature: Local table

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email | idnumber |
      | teacher1 | Teacher | 1 | teacher1@example.com | T1 |
      | student1 | Student | 1 | student1@example.com | S1 |
    And the following "courses" exist:
          | fullname | shortname | category | enablecompletion |
          | Course 1 | C1        | 0 | 1 |

  Scenario: Load course table
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/test.php"
    Then I should see "Course 1"
