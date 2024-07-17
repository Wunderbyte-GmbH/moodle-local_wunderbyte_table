"SELECT
                DISTINCT s1.*
                FROM (
                        SELECT DISTINCT  bo.id, bo.bookingid, bo.text, bo.maxanswers, bo.maxoverbooking, bo.minanswers, bo.bookingopeningtime, bo.bookingclosingtime, bo.courseid, bo.coursestarttime, bo.courseendtime, bo.enrolmentstatus, bo.description, bo.descriptionformat, bo.limitanswers, bo.timemodified, bo.addtocalendar, bo.calendarid, bo.pollurl, bo.groupid, bo.sent, bo.location, bo.institution, bo.address, bo.pollurlteachers, bo.howmanyusers, bo.pollsend, bo.removeafterminutes, bo.notificationtext, bo.notificationtextformat, bo.disablebookingusers, bo.sent2, bo.sentteachers, bo.beforebookedtext, bo.beforecompletedtext, bo.aftercompletedtext, bo.shorturl, bo.duration, bo.parentid, bo.semesterid, bo.dayofweektime, bo.invisible, bo.annotation, bo.identifier, bo.titleprefix, bo.priceformulaadd, bo.priceformulamultiply, bo.priceformulaoff, bo.dayofweek, bo.availability, bo.status, bo.responsiblecontact, bo.credits, bo.sortorder, bo.json, bo.sqlfilter  , cfd1.value as botags , cfd2.value as sport , cfd3.value as sportsdivision , cfd4.value as kurssprache , cfd5.value as format , cfd6.value as category , cfd7.value as organisation , cfd8.value as kompetenzen , cfd9.value as meinfeld , cfd10.value as orga , cfd11.value as kst , cfd12.value as zgcommunities  , STRING_AGG(CAST(bt1.teacherobject AS VARCHAR), ', ' ) as teacherobjects ,  f.filename  FROM {booking_options} bo  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_botags
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:botags_componentname
            ) cfd1
            ON bo.id = cfd1.instanceid  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_sport
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:sport_componentname
            ) cfd2
            ON bo.id = cfd2.instanceid  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_sportsdivision
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:sportsdivision_componentname
            ) cfd3
            ON bo.id = cfd3.instanceid  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_kurssprache
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:kurssprache_componentname
            ) cfd4
            ON bo.id = cfd4.instanceid  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_format
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:format_componentname
            ) cfd5
            ON bo.id = cfd5.instanceid  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_category
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:category_componentname
            ) cfd6
            ON bo.id = cfd6.instanceid  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_organisation
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:organisation_componentname
            ) cfd7
            ON bo.id = cfd7.instanceid  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_kompetenzen
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:kompetenzen_componentname
            ) cfd8
            ON bo.id = cfd8.instanceid  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_meinfeld
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:meinfeld_componentname
            ) cfd9
            ON bo.id = cfd9.instanceid  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_orga
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:orga_componentname
            ) cfd10
            ON bo.id = cfd10.instanceid  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_kst
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:kst_componentname
            ) cfd11
            ON bo.id = cfd11.instanceid  LEFT JOIN
            (
                SELECT cfd.instanceid, cfd.value
                FROM {customfield_data} cfd
                JOIN {customfield_field} cff
                ON cfd.fieldid=cff.id AND cff.shortname=:cf_zgcommunities
                JOIN {customfield_category} cfc
                ON cff.categoryid=cfc.id AND cfc.component=:zgcommunities_componentname
            ) cfd12
            ON bo.id = cfd12.instanceid   LEFT JOIN
        (
            SELECT bt.optionid,  '{"id":' || '' || u.id || '' || ', "firstname":"' || '' || u.firstname || '' || '", "lastname":"' || '' || u.lastname || '' || '", "name":"' || '' || u.lastname || '' || ', ' || '' || u.firstname || '' || '"}'  as teacherobject
            FROM {booking_teachers} bt
            JOIN {user} u
            ON bt.userid = u.id
        ) bt1
        ON bt1.optionid = bo.id   LEFT JOIN {files} f
            ON f.itemid=bo.id and f.component=:componentname3
            AND f.filearea=:bookingoptionimage
            AND f.mimetype LIKE 'image%' GROUP BY  bo.id ,  bo.bookingid ,  bo.text ,  bo.maxanswers ,  bo.maxoverbooking ,  bo.minanswers ,  bo.bookingopeningtime ,  bo.bookingclosingtime ,  bo.courseid ,  bo.coursestarttime ,  bo.courseendtime ,  bo.enrolmentstatus ,  bo.description ,  bo.descriptionformat ,  bo.limitanswers ,  bo.timemodified ,  bo.addtocalendar ,  bo.calendarid ,  bo.pollurl ,  bo.groupid ,  bo.sent ,  bo.location ,  bo.institution ,  bo.address ,  bo.pollurlteachers ,  bo.howmanyusers ,  bo.pollsend ,  bo.removeafterminutes ,  bo.notificationtext ,  bo.notificationtextformat ,  bo.disablebookingusers ,  bo.sent2 ,  bo.sentteachers ,  bo.beforebookedtext ,  bo.beforecompletedtext ,  bo.aftercompletedtext ,  bo.shorturl ,  bo.duration ,  bo.parentid ,  bo.semesterid ,  bo.dayofweektime ,  bo.invisible ,  bo.annotation ,  bo.identifier ,  bo.titleprefix ,  bo.priceformulaadd ,  bo.priceformulamultiply ,  bo.priceformulaoff ,  bo.dayofweek ,  bo.availability ,  bo.status ,  bo.responsiblecontact ,  bo.credits ,  bo.sortorder ,  bo.json ,  bo.sqlfilter   ,  cfd1.value  ,  cfd2.value  ,  cfd3.value  ,  cfd4.value  ,  cfd5.value  ,  cfd6.value  ,  cfd7.value  ,  cfd8.value  ,  cfd9.value  ,  cfd10.value  ,  cfd11.value  ,  cfd12.value  ,   f.filename
                    ) s1
                WHERE 1=1  AND (  bookingid = 8  OR  bookingid = 1  OR  bookingid = 3  OR  bookingid = 4  OR  bookingid = 6  OR  bookingid = 7  OR  bookingid = 2  OR  bookingid = 9  OR  bookingid = 5  )  AND  (
                        sqlfilter < 1 OR (
                            ((bookingopeningtime < 1 OR bookingopeningtime < :bookingopeningtimenow1)
                  AND (bookingclosingtime < 1 OR bookingclosingtime > :bookingopeningtimenow2)) AND
                    availability IS NOT NULL
                    AND (NOT availability::jsonb @> '[{"sqlfilter": "1"}]'::jsonb)
                            )
                        )
                         AND  (courseendtime > :timenow OR courseendtime = 0)
                ORDER BY text ASC NULLS FIRST"