/*
+------------------------------------------------------------+
| LICENSE                                                    |
+------------------------------------------------------------+
*/

/**
 * Copyright 2013 Michael C. Montero
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
+------------------------------------------------------------+
| SOURCE SCHEMA                                              |
+------------------------------------------------------------+
*/

create database if not exists schema_differ_source;

     grant all privileges
        on schema_differ_source.*
        to ''@'localhost'
identified by '';

drop table if exists schema_differ_source.diff_table;
create table schema_differ_source.diff_table
(
    col_a integer unsigned not null primary key,
    col_b varchar(30) not null default 'abc',
    col_c bigint(10) unsigned not null unique
);

drop table if exists schema_differ_source.add_table;
create table schema_differ_source.add_table
(
    col_c integer not null primary key
);

drop table if exists schema_differ_source.add_ref_table;
create table schema_differ_source.add_ref_table
(
    id integer unsigned not null auto_increment primary key,
    value varchar(32) not null,
    display_order integer not null
);

/*
+------------------------------------------------------------+
| TARGET SCHEMA                                              |
+------------------------------------------------------------+
*/

create database if not exists schema_differ_target;

     grant all privileges
        on schema_differ_target.*
        to ''@'localhost'
identified by '';

drop table if exists schema_differ_target.diff_table;
create table schema_differ_target.diff_table
(
    col_a integer,
    col_b varchar(10),
    col_z bigint
);

drop table if exists schema_differ_target.remove_table;
create table schema_differ_target.remove_table
(
    col_d integer not null primary key
);

drop table if exists schema_differ_target.remove_ref_table;
create table schema_differ_target.remove_ref_table
(
    id integer unsigned not null auto_increment primary key,
    value varchar(32) not null,
    display_order integer not null
);

flush privileges;
