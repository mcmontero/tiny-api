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

drop table if exists schema_differ_source.schema_differ_ref_add;
create table schema_differ_source.schema_differ_ref_add
(
    id int unsigned not null auto_increment primary key,
    value varchar(32) not null,
    display_order int not null
) engine = innodb default charset = utf8 collate = utf8_unicode_ci;

drop table if exists schema_differ_source.schema_differ_add;
create table schema_differ_source.schema_differ_add
(
    col_a int unsigned not null primary key,
    col_b varchar(30) not null collate utf8_unicode_ci default 'abc',
    col_c bigint(10) unsigned not null unique
) engine = innodb default charset = utf8 collate = utf8_unicode_ci;

drop table if exists schema_differ_source.schema_differ_cols;
create table schema_differ_source.schema_differ_cols
(
    col_a int unsigned not null unique,
    col_b varchar(100) not null,
    col_c char(3) default 'abc'
) engine = innodb default charset = utf8 collate = utf8_unicode_ci;

drop table if exists schema_differ_source.schema_differ_fks;
create table schema_differ_source.schema_differ_fks
(
    id int unsigned not null,
    col_a int unsigned not null
) engine = innodb default charset = utf8 collate = utf8_unicode_ci;

alter table schema_differ_source.schema_differ_fks
        add constraint schema_differ_fks_0_fk
    foreign key (id)
 references schema_differ_source.schema_differ_ref_add (id);

alter table schema_differ_source.schema_differ_fks
        add constraint schema_differ_fks_1_fk
    foreign key (col_a)
 references schema_differ_source.schema_differ_add (col_a)
  on delete cascade;

insert into schema_differ_source.schema_differ_ref_add
(
    id,
    value,
    display_order
)
values
(
    1,
    'abc',
    1
);

insert into schema_differ_source.schema_differ_ref_add
(
    id,
    value,
    display_order
)
values
(
    2,
    'def',
    2
);

/*
+------------------------------------------------------------+
| TARGET SCHEMA                                              |
+------------------------------------------------------------+
*/

drop table if exists schema_differ_target.schema_differ_ref_drop;
create table schema_differ_target.schema_differ_ref_drop
(
    id int unsigned not null auto_increment primary key,
    value varchar(32) not null,
    display_order int not null
) engine = innodb default charset = utf8 collate = utf8_unicode_ci;

drop table if exists schema_differ_target.schema_differ_drop;
create table schema_differ_target.schema_differ_drop
(
    col_a int unsigned not null primary key,
    col_b varchar(30) not null collate utf8_unicode_ci default 'abc',
    col_c bigint(10) unsigned not null unique
) engine = innodb default charset = utf8 collate = utf8_unicode_ci;

drop table if exists schema_differ_target.schema_differ_cols;
create table schema_differ_target.schema_differ_cols
(
    col_a int unsigned not null unique,
    col_b varchar(50),
    col_z int
) engine = innodb default charset = utf8 collate = utf8_unicode_ci;

drop table if exists schema_differ_target.schema_differ_fks;
create table schema_differ_target.schema_differ_fks
(
    id int unsigned not null,
    col_a int unsigned not null
) engine = innodb default charset = utf8 collate = utf8_unicode_ci;

alter table schema_differ_target.schema_differ_fks
        add constraint schema_differ_fks_100_fk
    foreign key (id)
 references schema_differ_target.schema_differ_drop (col_a);

alter table schema_differ_target.schema_differ_fks
        add constraint schema_differ_fks_1_fk
    foreign key (col_a)
 references schema_differ_target.schema_differ_cols (col_a);
