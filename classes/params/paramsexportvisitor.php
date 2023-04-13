<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Demofile to see how wunderbyte_table works.
 *
 * @package     local_wunderbyte_table
 * @copyright   2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class paramsexportvisitor implements visitor {

    public $visitoroutput = [];

    public function visitCountlabel(Countlabel $c) {

        if ($c->returnvalue() != "") {
            array_push($this->visitoroutput, $c->returnvalue());
        } else {
            //error
        }
    }

    public function visitPagination(Pagination $p) {

    }

    public function getvisitoroutput() {
        return $this->visitoroutput;
    }


    /*
    *
    In der ausführenden Klasse
        $params = [
            new Pagination(enabled true, numberofpages 20),
            new Countlabel(enabled true),
            new Rowdisplayselect(enabled true, stepstodisplayrows 5)
            ....
        ];

        $visitor = new Paramsexportvisitor();

        foreach ($params as $param) {
            $param->accept($visitor);
        }

        array_push($outputdata, $visitor->getvisitoroutput());
    */


}