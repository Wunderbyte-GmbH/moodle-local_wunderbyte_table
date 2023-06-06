@local @local_wunderbyte_table
Feature: Timespan filtering functionality of wunderbyte_table works as expected

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
      | fullname | shortname | startdate  | enddate    |
      | Course 1 | C1        | 1652317261 | 1652835661 |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name       | intro      | course | idnumber |
      | page     | PageName1  | PageDesc1  | C1     | PAGE1    |

@javascript
  Scenario: Filter course table in wb_table by timespan for overlaping
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Course"
    And I should see "Course 1" in the "#Course_r2" "css_element"
    And I click on "[aria-controls=\"id_collapse_startdate\"]" "css_element"
    And I set the field "date-startdate" in the "#id_collapse_startdate" "css_element" to "2022-05-13"
    And I set the field "date-enddate" in the "#id_collapse_startdate" "css_element" to "2022-05-17"
    And I set the field "Display records" in the "#id_collapse_startdate" "css_element" to "overlap"
    ## And I set the following fields to these values:
    ##  | date-startdate | ## 18 days ago ## |
    ##  | date-enddate | ## 12 days ago ## |
    ##  | Display records | overlap |
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to "checked"
    And I wait "1" seconds
    And I should see "Course 1" in the "#Course_r1" "css_element"
    And I set the field "Display records" in the "#id_collapse_startdate" "css_element" to "within"
    And I should not see "Course 1" in the ".wunderbyteTableClass.Course" "css_element"
