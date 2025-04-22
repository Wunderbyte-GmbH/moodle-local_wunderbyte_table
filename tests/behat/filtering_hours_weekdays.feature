@local @local_wunderbyte_table
Feature: Hours and weekdays filtering functionality of wunderbyte_table works as expected

  Background:
    Given the following config values are set as admin:
      | config        | value         |
      | texteditors   | atto,textarea |
      | timezone      | Europe/Brussels |
      | forcetimezone | Europe/Brussels |
    ## Unfortunately, TinyMCE is slow and has misbehavior which might cause number of site-wide issues. So - we disable it.
    ## Forcing of timezome could be important for date validation.
    And the following "users" exist:
      | username | firstname | lastname | timecreated                                        |
      | user1    | Username  | 1        | ## Friday, February 10, 2023 04:03 GMT+01:00 ##    |
      | user2    | Username  | 2        | ## Friday, February 10, 2023 14:05 GMT+01:00 ##    |
      | user3    | Username  | 3        | ## Monday, February 20, 2023 20:25 GMT+01:00 ##    |
      | user4    | Username  | 4        | ## Tuesday, February 21, 2023 10:25 GMT+01:00 ##   |
      | user5    | Username  | 5        | ## Wednesday, February 22, 2023 11:15 GMT+01:00 ## |
      | user6    | Username  | 6        | ## Wednesday, March 1, 2023 15:35 GMT+01:00 ##     |
      | user7    | Username  | 7        | ## Friday, March 10, 2023 04:05 GMT+01:00 ##       |
      | user8    | Username  | 8        | ## Friday, March 10, 2023 12:05 GMT+01:00 ##       |
      | user9    | Username  | 9        | ## Monday, March 20, 2023 17:25 GMT+01:00 ##       |
      | user10   | Username  | 10       | ## Wednesday, May 1, 2024 9:35 GMT+01:00 ##        |
      | user11   | Username  | 11       | ## Tuesday, May 7, 2024 19:35 GMT+01:00 ##         |
      | user12   | Username  | 12       | ## Friday, May 17, 2024 16:35 GMT+01:00 ##         |
      | teacher1 | Teacher   | 1        | ## Monday, May 27, 2024 22:35 GMT+01:00 ##         |
    And the following "courses" exist:
      | fullname | shortname | startdate  | enddate    | timecreated                             |
      | Course 1 | C1        | 1652317261 | 1652835661 | ## February 10, 2021 4:13 GMT+01:00 ##  |
      | Course 2 | C2        | 1683853261 | 1684371661 | ## February 10, 2022 14:05 GMT+01:00 ## |
      | Course 3 | C3        | 1652317261 | 1652835661 | ## February 10, 2021 4:33 GMT+01:00 ##  |
      | Course 4 | C4        | 1683853261 | 1684371661 | ## February 10, 2022 18:05 GMT+01:00 ## |
      | Course 5 | C5        | 1652317261 | 1652835661 | ## February 10, 2021 11:33 GMT+01:00 ## |
      | Course 6 | C6        | 1683853261 | 1684371661 | ## February 10, 2022 23:15 GMT+01:00 ## |
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
  Scenario: Filter users table in wb_table by weekdays
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 4"
    ## Filter panel being hidden by default on this tab
    And I click on ".asidecollapse-demotable_4" "css_element"
    And I click on ".demotable_4 [aria-controls=\"id_collapse_timecreated\"]" "css_element"
    When I set the field "Friday" in the ".demotable_4 #id_collapse_timecreated" "css_element" to "checked"
    ##And I wait until the page is ready - does bot work in this case.
    And I wait "2" seconds
    Then I should see "5 of 15 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I should see "user1" in the "#demotable_4_r1" "css_element"
    And I should see "user12" in the "#demotable_4_r2" "css_element"
    And I should see "user2" in the "#demotable_4_r3" "css_element"
    And I should see "user7" in the "#demotable_4_r4" "css_element"
    And I should see "user8" in the "#demotable_4_r5" "css_element"
    And I set the field "Friday" in the ".demotable_4 #id_collapse_timecreated" "css_element" to ""
    And I wait until the page is ready
    And I set the field "Wednesday" in the ".demotable_4 #id_collapse_timecreated" "css_element" to "checked"
    And I wait until the page is ready
    And I should see "user10" in the "#demotable_4_r1" "css_element"
    And I should see "user5" in the "#demotable_4_r2" "css_element"
    And I should see "user6" in the "#demotable_4_r3" "css_element"
    And I should see "3 of 15 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I set the field "Wednesday" in the ".demotable_4 #id_collapse_timecreated" "css_element" to ""
    And I wait until the page is ready
    And I set the field "Thursday" in the ".demotable_4 #id_collapse_timecreated" "css_element" to "checked"
    And I wait until the page is ready
    And I should see "admin" in the "#demotable_4_r1" "css_element"
    And I should see "guest" in the "#demotable_4_r2" "css_element"
    And I should see "2 of 15 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"

  @javascript
  Scenario: Filter course table in wb_table by fullname and hourlist
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 2"
    ## Filter panel being hidden by default on this tab
    And I click on ".asidecollapse-demotable_2" "css_element"
    ## We have to hide site's row 1st - - because we do not know "timecreate" for site!
    And I click on ".demotable_2 [aria-controls=\"id_collapse_fullname\"]" "css_element"
    And I set the field "Course 1" in the ".demotable_2 #id_collapse_fullname" "css_element" to "checked"
    And I wait until the page is ready
    And I set the field "Course 2" in the ".demotable_2 #id_collapse_fullname" "css_element" to "checked"
    And I wait until the page is ready
    And I set the field "Course 3" in the ".demotable_2 #id_collapse_fullname" "css_element" to "checked"
    And I wait until the page is ready
    And I set the field "Course 4" in the ".demotable_2 #id_collapse_fullname" "css_element" to "checked"
    And I wait until the page is ready
    And I set the field "Course 5" in the ".demotable_2 #id_collapse_fullname" "css_element" to "checked"
    And I wait until the page is ready
    And I set the field "Course 6" in the ".demotable_2 #id_collapse_fullname" "css_element" to "checked"
    And I wait until the page is ready
    And I should see "6 of 7 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    ## Hide filter - required for a new filter tool
    And I click on "//div[contains(@class, 'demotable_2')]//aside[contains(@class, 'wunderbyte_table_components')]" "xpath_element"
    ## Use hourlist filrer now
    And I click on ".demotable_2 [aria-controls=\"id_collapse_timecreated\"]" "css_element"
    When I set the field "04:00 - 05:00" in the ".demotable_2 #id_collapse_timecreated" "css_element" to "checked"
    ##And I wait until the page is ready - does bot work in this case.
    And I wait "2" seconds
    Then I should see "2 of 7 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I should see "Course 1" in the "#demotable_2_r1" "css_element"
    And I should see "Course 3" in the "#demotable_2_r2" "css_element"
    And "//*[contains(@id, 'demotable_2')]//tr[@id, 'demotable_2_r3']" "xpath_element" should not exist
    And I set the field "04:00 - 05:00" in the ".demotable_2 #id_collapse_timecreated" "css_element" to ""
    And I wait until the page is ready
    And I set the field "11:00 - 12:00" in the ".demotable_2 #id_collapse_timecreated" "css_element" to "checked"
    And I wait until the page is ready
    And I should see "Course 5" in the "#demotable_2_r1" "css_element"
    And "//*[contains(@id, 'demotable_2')]//tr[@id, 'demotable_2_r2']" "xpath_element" should not exist
    And I should see "1 of 7 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
    And I set the field "11:00 - 12:00" in the ".demotable_2 #id_collapse_timecreated" "css_element" to ""
    And I wait until the page is ready
    And I set the field "23:00 - 24:00" in the ".demotable_2 #id_collapse_timecreated" "css_element" to "checked"
    And I wait until the page is ready
    And I should see "Course 6" in the "#demotable_2_r1" "css_element"
    And "//*[contains(@id, 'demotable_2')]//tr[@id, 'demotable_2_r2']" "xpath_element" should not exist
    And I should see "1 of 7 records found" in the ".tab-pane.active .wb-records-count-label" "css_element"
