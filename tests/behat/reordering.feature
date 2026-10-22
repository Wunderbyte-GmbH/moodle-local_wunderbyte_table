@local @local_wunderbyte_table @javascript
Feature: Drag and drop reordering of wunderbyte_table rows cleans up after itself

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | Username  | 1        |
      | user2    | Username  | 2        |
    And I clean wbtable cache
    And I log in as "admin"
    And I am on fixture page "/local/wunderbyte_table/tests/behat/fixtures/sortablerows.php"

  Scenario: Highlighting of a dragged row is removed when the drag ends without a reorder
    When I trigger the sortable list event "DRAGSTART" on the first row of the wunderbyte table
    And I trigger the sortable list event "DRAG" on the first row of the wunderbyte table
    Then ".wb-table-sortable-active" "css_element" should exist
    And ".wb-table-sortable-drag" "css_element" should exist
    # Row dropped at its old position or drag cancelled: only the dragend event is fired.
    And I trigger the sortable list event "DRAGEND" on the first row of the wunderbyte table
    And ".wb-table-sortable-active" "css_element" should not exist
    And ".wb-table-sortable-drag" "css_element" should not exist
