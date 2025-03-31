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
      | Course 2 | C2        | 1683853261 | 1684371661 |
    ## C1 - 12-18 May 2022, C2 - 12-18 May 2023
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name       | intro      | course | idnumber |
      | page     | PageName1  | PageDesc1  | C1     | PAGE1    |
      | page     | PageName2  | PageDesc2  | C2     | PAGE2    |
    And I change viewport size to "1600x3000"
    And I clean wbtable cache

  @javascript
  Scenario: Filter course table in wb_table by timespan for overlaping
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 2"
    ## Filter panel being hidden by default on the Course tab
    And I click on ".asidecollapse-demotable_2" "css_element"
    And I should see "Course 1" in the "#demotable_2_r2" "css_element"
    And I click on "[aria-controls=\"id_collapse_startdate\"]" "css_element"
    And I set the field "date-startdate" in the "#id_collapse_startdate" "css_element" to "2022-05-13"
    And I set the field "date-enddate" in the "#id_collapse_startdate" "css_element" to "2022-05-17"
    And I set the field "Display records" in the "#id_collapse_startdate" "css_element" to "flexoverlap"
    ## And I set the following fields to these values:
    ##  | date-startdate | ## 18 days ago ## |
    ##  | date-enddate | ## 12 days ago ## |
    ##  | Display records | overlap |
    And I wait "2" seconds
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to "checked"
    And I wait "2" seconds
    Then I should see "Course 1" in the "#demotable_2_r1" "css_element"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to ""
    And I wait "1" seconds
    And I set the field "date-startdate" in the "#id_collapse_startdate" "css_element" to "2023-05-13"
    And I set the field "date-enddate" in the "#id_collapse_startdate" "css_element" to "2023-05-17"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to "checked"
    And I wait "1" seconds
    Then I should see "Course 2" in the "#demotable_2_r1" "css_element"
    And I should not see "Course 1" in the ".wunderbyteTableClass.demotable_2" "css_element"

  @javascript
  Scenario: Filter course table in wb_table by timespan for within
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 2"
    ## Filter panel being hidden by default on the Course tab
    And I click on ".asidecollapse-demotable_2" "css_element"
    And I should see "Course 1" in the "#demotable_2_r2" "css_element"
    And I should see "Course 2" in the "#demotable_2_r3" "css_element"
    And I click on "[aria-controls=\"id_collapse_startdate\"]" "css_element"
    And I set the field "date-startdate" in the "#id_collapse_startdate" "css_element" to "2022-05-11"
    And I set the field "date-enddate" in the "#id_collapse_startdate" "css_element" to "2022-05-19"
    And I set the field "Display records" in the "#id_collapse_startdate" "css_element" to "within"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to "checked"
    And I wait "1" seconds
    Then I should see "Course 1" in the "#demotable_2_r1" "css_element"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to ""
    And I wait "1" seconds
    And I set the field "date-startdate" in the "#id_collapse_startdate" "css_element" to "2023-05-11"
    And I set the field "date-enddate" in the "#id_collapse_startdate" "css_element" to "2023-05-19"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to "checked"
    And I wait "1" seconds
    Then I should see "Course 2" in the "#demotable_2_r1" "css_element"
    And I should not see "Course 1" in the ".wunderbyteTableClass.demotable_2" "css_element"

  @javascript
  Scenario: Filter course table in wb_table by timespan for before and after
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 2"
    ## Filter panel being hidden by default on the Course tab
    And I click on ".asidecollapse-demotable_2" "css_element"
    And I should see "Course 1" in the "#demotable_2_r2" "css_element"
    And I should see "Course 2" in the "#demotable_2_r3" "css_element"
    And I click on "[aria-controls=\"id_collapse_startdate\"]" "css_element"
    And I set the field "date-startdate" in the "#id_collapse_startdate" "css_element" to "2023-05-10"
    And I set the field "date-enddate" in the "#id_collapse_startdate" "css_element" to "2023-05-11"
    And I set the field "Display records" in the "#id_collapse_startdate" "css_element" to "before"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to "checked"
    And I wait "1" seconds
    Then I should see "Acceptance test site" in the "#demotable_2_r1" "css_element"
    And I should see "Course 1" in the "#demotable_2_r2" "css_element"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to ""
    And I wait "1" seconds
    And I set the field "Display records" in the "#id_collapse_startdate" "css_element" to "after"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to "checked"
    And I wait "1" seconds
    Then I should see "Course 2" in the "#demotable_2_r1" "css_element"
    And I should not see "Course 1" in the ".wunderbyteTableClass.demotable_2" "css_element"

  @javascript
  Scenario: Filter course table in wb_table by timespan for overlap beginning
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 2"
    ## Filter panel being hidden by default on the Course tab
    And I click on ".asidecollapse-demotable_2" "css_element"
    And I should see "Course 1" in the "#demotable_2_r2" "css_element"
    And I click on "[aria-controls=\"id_collapse_startdate\"]" "css_element"
    And I set the field "date-startdate" in the "#id_collapse_startdate" "css_element" to "2022-05-13"
    And I set the field "date-enddate" in the "#id_collapse_startdate" "css_element" to "2022-05-20"
    And I set the field "Display records" in the "#id_collapse_startdate" "css_element" to "overlapping beginning"
    And I wait "2" seconds
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to "checked"
    And I wait "2" seconds
    Then I should see "Course 1" in the "#demotable_2_r1" "css_element"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to ""
    And I wait "1" seconds
    And I set the field "date-startdate" in the "#id_collapse_startdate" "css_element" to "2023-05-13"
    And I set the field "date-enddate" in the "#id_collapse_startdate" "css_element" to "2023-05-20"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to "checked"
    And I wait "1" seconds
    Then I should see "Course 2" in the "#demotable_2_r1" "css_element"
    And I should not see "Course 1" in the ".wunderbyteTableClass.demotable_2" "css_element"

  @javascript
  Scenario: Filter course table in wb_table by timespan for overlap ending
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 2"
    ## Filter panel being hidden by default on the Course tab
    And I click on ".asidecollapse-demotable_2" "css_element"
    And I should see "Course 1" in the "#demotable_2_r2" "css_element"
    And I click on "[aria-controls=\"id_collapse_startdate\"]" "css_element"
    And I set the field "date-startdate" in the "#id_collapse_startdate" "css_element" to "2022-05-10"
    And I set the field "date-enddate" in the "#id_collapse_startdate" "css_element" to "2022-05-15"
    And I set the field "Display records" in the "#id_collapse_startdate" "css_element" to "overlapping ending"
    And I wait "2" seconds
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to "checked"
    And I wait "2" seconds
    Then I should see "Course 1" in the "#demotable_2_r1" "css_element"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to ""
    And I wait "1" seconds
    And I set the field "date-startdate" in the "#id_collapse_startdate" "css_element" to "2023-05-10"
    And I set the field "date-enddate" in the "#id_collapse_startdate" "css_element" to "2023-05-15"
    And I set the field "startdate" in the "#id_collapse_startdate" "css_element" to "checked"
    And I wait "1" seconds
    Then I should see "Course 2" in the "#demotable_2_r1" "css_element"
    And I should not see "Course 1" in the ".wunderbyteTableClass.demotable_2" "css_element"

  @javascript
  Scenario: Filter course table in wb_table ended before the exact end date
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 2"
    ## Filter panel being hidden by default on the Course tab
    And I click on ".asidecollapse-demotable_2" "css_element"
    And I should see "Course 1" in the "#demotable_2_r2" "css_element"
    And I click on "[aria-controls=\"id_collapse_enddate\"]" "css_element"
    And I set the field "enddate_single-date" in the "#id_collapse_enddate" "css_element" to "2023-05-13"
    And I set the field "enddate" in the "#id_collapse_enddate" "css_element" to "checked"
    And I wait "1" seconds
    And I should not see "Course 2" in the ".wunderbyteTableClass.demotable_2" "css_element"
