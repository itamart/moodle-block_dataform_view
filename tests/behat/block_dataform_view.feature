@block @block_dataform_view @mod_dataform
Feature: Block dataform view
    In order to populate Dataform activity content in a course
    As a manager
    I can add dataform view block in a course or on the frontpage

    @javascript
    Scenario: Add dataform view block on the frontpage
        ### Background ###

        Given I start afresh with dataform "Test Dataform"
        And the following dataform "fields" exist:
            | type  | dataform  | name        |
            | text  | dataform1 | Field Text  |

        And the following dataform "views" exist:
            | type      | dataform  | name         |
            | aligned   | dataform1 | View Aligned |

        And the following dataform "entries" exist:
            | dataform  | user          | group | timecreated   | timemodified  | Field Text                |
            | dataform1 | teacher1      |       |               |               | 1 Entry by Teacher 01     |
            | dataform1 | assistant1    |       |               |               | 2 Entry by Assistant 01   |
            | dataform1 | student1      |       |               |               | 3 Entry by Student 01     |
            | dataform1 | student2      |       |               |               | 4 Entry by Student 02     |
            | dataform1 | student3      |       |               |               | 5 Entry by Student 03     |
 
        Given I log in as "admin"
        And I follow "Turn editing on"
        
        Then I add the "Dataform view" block
        And I open the "Dataform view" blocks action menu
        And I follow "Configure Dataform view block"
        And I set the following fields to these values:
          | Select a dataform | Test Dataform |
        And I press "Save changes"
        And I set the following fields to these values:
          | Select a view | View Aligned |
        And I press "Save changes"
        
        Then I see "1 Entry by Teacher 01"
        And I see "2 Entry by Assistant 01"
        And I see "3 Entry by Student 01"
        And I see "4 Entry by Student 02"
        And I see "5 Entry by Student 03"
