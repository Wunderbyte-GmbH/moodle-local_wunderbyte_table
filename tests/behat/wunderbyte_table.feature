@local @local_wunderbyte_table
Feature: Local table

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email | idnumber |
      | teacher1 | Teacher | 1 | teacher1@example.com | T1 |
      | student1 | Student | 1 | student1@example.com | S1 |

    Scenario:
      Given I log in as "admin"
      When I visit "/local/wunderbyte_table/test.php"
      Then I should see "Testing table class"
      When I set the field "Download table data as" to "Comma separated values(.csv)"
      And I press "Download"
      