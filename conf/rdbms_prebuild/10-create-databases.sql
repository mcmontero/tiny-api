create database if not exists schema_differ_source;

     grant all privileges
        on schema_differ_source.*
        to ''@'localhost'
identified by '';

drop table if exists schema_differ_source.diff_table;
create table schema_differ_source.diff_table
(
    col_a integer unsigned not null primary key,
    col_b varchar(30) not null
);

drop table if exists schema_differ_source.add_table;
create table schema_differ_source.add_table
(
    col_c integer not null primary key
);

create database if not exists schema_differ_target;

     grant all privileges
        on schema_differ_target.*
        to ''@'localhost'
identified by '';

drop table if exists schema_differ_target.diff_table;
create table schema_differ_target.diff_table
(
    col_a integer,
    col_b varchar(10)
);

drop table if exists schema_differ_target.remove_table;
create table schema_differ_target.remove_table
(
    col_d integer not null primary key
);

flush privileges;
