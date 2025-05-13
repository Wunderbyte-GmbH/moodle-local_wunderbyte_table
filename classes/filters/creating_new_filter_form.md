```mermaid
    graph LR
%% Definitions
formmanager[filter_manager]
columnmanager[column_manager]
formoperator[filter_form_operator]
Operator[filter_form_operator]
filter[Filter]
action[Action]

formmanager -- render_mandatory_fields --> formoperator
```