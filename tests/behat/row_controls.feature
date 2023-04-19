@local @local_wunderbyte_table
Feature: Row controls functionality of wunderbyte_table works as expected

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
  Scenario: Press TriggersNoModal button in the rows on the deffirent tabs of the table
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Users"
    Then I should see "admin" in the "#Users_r1" "css_element"
    And I click on "TriggersNoModal" "link" in the "#Users_r1" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"
    And I follow "Course"
    Then I should see "Course 1" in the "#Course_r2" "css_element"
    And I click on "TriggersNoModal" "link" in the "#Course_r2" "css_element"
    And I wait "1" seconds
    And I should see "Did work" in the "#user-notifications" "css_element"
