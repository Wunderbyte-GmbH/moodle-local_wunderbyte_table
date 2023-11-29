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
      | user13   | Username  | 13       |
      | user14   | Username  | 14       |
      | user15   | Username  | 15       |
      | user16   | Username  | 16       |
      | user17   | Username  | 17       |
      | user18   | Username  | 18       |
      | user19   | Username  | 19       |
      | user20   | Username  | 20       |
      | user21   | Username  | 21       |
      | teacher1 | Teacher   | 1        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name       | intro      | course | idnumber |
      | page     | PageName1  | PageDesc1  | C1     | PAGE1    |

  @javascript
  Scenario: Wunderbyte Table: sort Users tab table using select field
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    And I wait until the page is ready
    And I set the field "selectsortcolumn-fa5e95f7721ad449d1cdd30f461d1cec" to "id"
    And I wait "1" seconds
    And ".fa5e95f7721ad449d1cdd30f461d1cec th.id.asc" "css_element" should exist
    And I should see "guest" in the "#fa5e95f7721ad449d1cdd30f461d1cec_r1" "css_element"
    And I follow "changesortorder-fa5e95f7721ad449d1cdd30f461d1cec"
    And I wait "1" seconds
    And ".fa5e95f7721ad449d1cdd30f461d1cec th.id.desc" "css_element" should exist
    And I should see "teacher1" in the "#fa5e95f7721ad449d1cdd30f461d1cec_r1" "css_element"
    And I set the field "selectsortcolumn-fa5e95f7721ad449d1cdd30f461d1cec" to "username"
    And I wait "1" seconds
    And ".fa5e95f7721ad449d1cdd30f461d1cec th.username.desc" "css_element" should exist
    And I should see "user9" in the "#fa5e95f7721ad449d1cdd30f461d1cec_r1" "css_element"
    And I follow "changesortorder-fa5e95f7721ad449d1cdd30f461d1cec"
    And I wait "1" seconds
    And ".fa5e95f7721ad449d1cdd30f461d1cec th.username.asc" "css_element" should exist
    And I should see "admin" in the "#fa5e95f7721ad449d1cdd30f461d1cec_r1" "css_element"
    And I set the field "selectsortcolumn-fa5e95f7721ad449d1cdd30f461d1cec" to "lastname"
    And I wait "1" seconds
    And ".fa5e95f7721ad449d1cdd30f461d1cec th.lastname.asc" "css_element" should exist
    And I should see "guest" in the "#fa5e95f7721ad449d1cdd30f461d1cec_r1" "css_element"
    And I follow "changesortorder-fa5e95f7721ad449d1cdd30f461d1cec"
    And I wait "1" seconds
    And ".fa5e95f7721ad449d1cdd30f461d1cec th.lastname.desc" "css_element" should exist
    And I should see "admin" in the "#fa5e95f7721ad449d1cdd30f461d1cec_r1" "css_element"
    And I set the field "selectsortcolumn-fa5e95f7721ad449d1cdd30f461d1cec" to "firstname"
    And I wait "1" seconds
    And ".fa5e95f7721ad449d1cdd30f461d1cec th.firstname.desc" "css_element" should exist
    And I should see "user1" in the "#fa5e95f7721ad449d1cdd30f461d1cec_r1" "css_element"
    And I follow "changesortorder-fa5e95f7721ad449d1cdd30f461d1cec"
    And I wait "1" seconds
    And ".fa5e95f7721ad449d1cdd30f461d1cec th.firstname.asc" "css_element" should exist
    And I should see "admin" in the "#fa5e95f7721ad449d1cdd30f461d1cec_r1" "css_element"

  @javascript
  Scenario: Wunderbyte Table: sort Course tab table using select field
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 2"
    And I wait until the page is ready
    And I set the field "selectsortcolumn-d82aa03a37e6ab3d58369289ffadc665" to "id"
    And I wait "1" seconds
    And ".d82aa03a37e6ab3d58369289ffadc665 th.id.asc" "css_element" should exist
    And I should see "Acceptance test site" in the "#d82aa03a37e6ab3d58369289ffadc665_r1" "css_element"
    And I should see "Course 2" in the "#d82aa03a37e6ab3d58369289ffadc665_r3" "css_element"
    And I follow "changesortorder-d82aa03a37e6ab3d58369289ffadc665"
    And I wait "1" seconds
    And ".d82aa03a37e6ab3d58369289ffadc665 th.id.desc" "css_element" should exist
    And I should see "Course 2" in the "#d82aa03a37e6ab3d58369289ffadc665_r1" "css_element"
    And I should see "Acceptance test site" in the "#d82aa03a37e6ab3d58369289ffadc665_r3" "css_element"
    And I set the field "selectsortcolumn-d82aa03a37e6ab3d58369289ffadc665" to "fullname"
    And I wait "1" seconds
    And ".d82aa03a37e6ab3d58369289ffadc665 th.fullname.desc" "css_element" should exist
    And I should see "Course 2" in the "#d82aa03a37e6ab3d58369289ffadc665_r1" "css_element"
    And I should see "Acceptance test site" in the "#d82aa03a37e6ab3d58369289ffadc665_r3" "css_element"
    And I follow "changesortorder-d82aa03a37e6ab3d58369289ffadc665"
    And I wait "1" seconds
    And ".d82aa03a37e6ab3d58369289ffadc665 th.fullname.asc" "css_element" should exist
    And I should see "Acceptance test site" in the "#d82aa03a37e6ab3d58369289ffadc665_r1" "css_element"
    And I should see "Course 2" in the "#d82aa03a37e6ab3d58369289ffadc665_r3" "css_element"
    And I set the field "selectsortcolumn-d82aa03a37e6ab3d58369289ffadc665" to "shortname"
    And I wait "1" seconds
    And ".d82aa03a37e6ab3d58369289ffadc665 th.shortname.asc" "css_element" should exist
    And I should see "Acceptance test site" in the "#d82aa03a37e6ab3d58369289ffadc665_r1" "css_element"
    And I should see "Course 2" in the "#d82aa03a37e6ab3d58369289ffadc665_r3" "css_element"
    And I follow "changesortorder-d82aa03a37e6ab3d58369289ffadc665"
    And I wait "1" seconds
    And ".d82aa03a37e6ab3d58369289ffadc665 th.shortname.desc" "css_element" should exist
    And I should see "Course 2" in the "#d82aa03a37e6ab3d58369289ffadc665_r1" "css_element"
    And I should see "Acceptance test site" in the "#d82aa03a37e6ab3d58369289ffadc665_r3" "css_element"

  @javascript
  ## It is important to use not a default sorting column in this test
  Scenario: Wunderbyte Table: sort Users tab table with column id
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 1"
    And I wait until the page is ready
    And I should see "ID" in the "th.id.wb-table-column" "css_element"
    And I click on "th.id.wb-table-column" "css_element"
    And I wait "1" seconds
    And ".fa5e95f7721ad449d1cdd30f461d1cec th.id.asc" "css_element" should exist
    And I should see "guest" in the "#fa5e95f7721ad449d1cdd30f461d1cec_r1" "css_element"
    And I click on "th.id.wb-table-column.asc" "css_element"
    And I wait "1" seconds
    And ".fa5e95f7721ad449d1cdd30f461d1cec th.id.desc" "css_element" should exist
    And I should see "teacher1" in the "#fa5e95f7721ad449d1cdd30f461d1cec_r1" "css_element"
    ## When clicking column header in table (for sorting) - sync value with the select "selectsortcolumn"
    And the field "selectsortcolumn-fa5e95f7721ad449d1cdd30f461d1cec" matches value "id"
    And ".wunderbyteTableSelect .fa-sort-alpha-desc.sortdown" "css_element" should exist

  @javascript
  ## It is important to use not a default sorting column in this test
  Scenario: Wunderbyte Table: Sort Course tab table with column Short name
    Given I log in as "admin"
    When I visit "/local/wunderbyte_table/demo.php"
    And I follow "Demo table 2"
    And I wait until the page is ready
    And ".d82aa03a37e6ab3d58369289ffadc665 th.fullname.asc" "css_element" should exist
    And I should see "Acceptance test site" in the "#d82aa03a37e6ab3d58369289ffadc665_r1" "css_element"
    And I should see "Short name" in the "th.shortname.wb-table-column" "css_element"
    And ".d82aa03a37e6ab3d58369289ffadc665 th.shortname.asc" "css_element" should not exist
    And ".d82aa03a37e6ab3d58369289ffadc665 th.shortname.desc" "css_element" should not exist
    And I click on "th.shortname.wb-table-column" "css_element"
    And I wait "1" seconds
    And ".d82aa03a37e6ab3d58369289ffadc665 th.shortname.asc" "css_element" should exist
    And I should see "Acceptance test site" in the "#d82aa03a37e6ab3d58369289ffadc665_r1" "css_element"
    And I click on "th.shortname.wb-table-column.asc" "css_element"
    And I wait "1" seconds
    And ".d82aa03a37e6ab3d58369289ffadc665 th.shortname.desc" "css_element" should exist
    And I should see "Course 2" in the "#d82aa03a37e6ab3d58369289ffadc665_r1" "css_element"
    ## When clicking column header in table (for sorting) - sync value with the select "selectsortcolumn"
    And the field "selectsortcolumn-d82aa03a37e6ab3d58369289ffadc665" matches value "Short name"
    And ".wunderbyteTableSelect .fa-sort-alpha-desc.sortdown" "css_element" should exist
