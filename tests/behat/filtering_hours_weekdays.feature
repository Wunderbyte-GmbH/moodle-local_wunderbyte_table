@local @local_wunderbyte_table
Feature: Hours and weekdays filtering functionality of wunderbyte_table works as expected

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | timecreated                              |
      | user1    | Username  | 1        | ## Friday, February 10, 2023 04:03 ##    |
      | user2    | Username  | 2        | ## Friday, February 10, 2023 14:05 ##    |
      | user3    | Username  | 3        | ## Monday, February 20, 2023 20:25 ##    |
      | user4    | Username  | 4        | ## Tuesday, February 21, 2023 10:25 ##   |
      | user5    | Username  | 5        | ## Wednesday, February 22, 2023 11:15 ## |
      | user6    | Username  | 6        | ## Wednesday, March 1, 2023 15:35 ##     |
      | user7    | Username  | 7        | ## Friday, March 10, 2023 04:05 ##       |
      | user8    | Username  | 8        | ## Friday, March 10, 2023 12:05 ##       |
      | user9    | Username  | 9        | ## Monday, March 20, 2023 17:25 ##       |
      | user10   | Username  | 10       | ## Wednesday, May 1, 2024 9:35 ##        |
      | user11   | Username  | 11       | ## Tuesday, May 7, 2024 19:35 ##         |
      | user12   | Username  | 12       | ## Friday, May 17, 2024 16:35 ##         |
      | teacher1 | Teacher   | 1        | ## Monday, May 27, 2024 22:35 ##         |
    And the following "courses" exist:
      | fullname | shortname | startdate  | enddate    | timecreated                   |
      | Course 1 | C1        | 1652317261 | 1652835661 | ## February 10, 2023 4:13 ##  |
      | Course 2 | C2        | 1683853261 | 1684371661 | ## February 10, 2023 14:05 ## |
    ## C1 - 12-18 May 2022, C2 - 12-18 May 2023
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name       | intro      | course | idnumber |
      | page     | PageName1  | PageDesc1  | C1     | PAGE1    |
      | page     | PageName2  | PageDesc2  | C2     | PAGE2    |
    ## Unfortunately, TinyMCE is slow and has misbehavior which might cause number of site-wide issues. So - we disable it.
    And the following config values are set as admin:
      | config        | value         |
      | texteditors   | atto,textarea |
    And I change viewport size to "1600x12000"
    ## Forcing of timezome is important for date validation
    ##  | timezone      | Europe/Berlin |
    ##  | forcetimezone | Europe/Berlin |

  @javascript
  Scenario: Filter users table in wb_table by weekdays
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 4"
    ## Filter panel being hidden by default on this tab
    And I click on ".asidecollapse-demotable_4" "css_element"
    And I click on ".demotable_4 [aria-controls=\"id_collapse_timecreated\"]" "css_element"
    When I set the field "Friday" in the ".demotable_4 #id_collapse_timecreated" "css_element" to "checked"
    And I wait until the page is ready
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
