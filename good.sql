
drop database simlitabmas; commit; 
create database simlitabmas;  
use simlitabmas; 


create table DOCUMENT
(
   DOCUMENT_ID          int not null,
   PATH_COMPLETE        varchar(1024) not null,
   PATH_BACKUP          varchar(1024) not null,
   MD5SUM               varchar(255) not null,
   primary key (DOCUMENT_ID)
);

/*==============================================================*/
/* Table: DOCUMENTUSULAN                                        */
/*==============================================================*/
alter table DOCUMENT change DOCUMENT_ID DOCUMENT_ID int AUTO_INCREMENT ;

create table DOCUMENTUSULAN
(
   DOCUMENT_USULAN_ID   int not null,
   DOCUMENT_ID          int not null,
   USULAN_ID            int not null,
   primary key (DOCUMENT_USULAN_ID)
);

/*==============================================================*/
/* Table: DOKUMENUSULANDIBUKA                                   */
/*==============================================================*/
alter table DOCUMENTUSULAN change DOCUMENT_USULAN_ID DOCUMENT_USULAN_ID int AUTO_INCREMENT ;

create table DOKUMENUSULANDIBUKA
(
   DOKUMENUSULANDIBUKA_ID int not null,
   DOCUMENT_ID          int not null,
   USULANDIBUKA_ID      int not null,
   primary key (DOKUMENUSULANDIBUKA_ID)
);

/*==============================================================*/
/* Table: OPTIONSSTATUSUSULANDIBUKA                             */
/*==============================================================*/
alter table DOKUMENUSULANDIBUKA change DOKUMENUSULANDIBUKA_ID DOKUMENUSULANDIBUKA_ID int AUTO_INCREMENT ;

create table OPTIONSSTATUSUSULANDIBUKA
(
   OPTIONSSTATUSUSULANDIBUKA_ID int not null,
   NAME                 varchar(255) not null,
   primary key (OPTIONSSTATUSUSULANDIBUKA_ID)
);

/*==============================================================*/
/* Table: STATUSUSULAN                                          */
/*==============================================================*/
alter table OPTIONSSTATUSUSULANDIBUKA change OPTIONSSTATUSUSULANDIBUKA_ID OPTIONSSTATUSUSULANDIBUKA_ID int AUTO_INCREMENT ;

create table STATUSUSULAN
(
   STATUSUSULAN_ID      int not null,
   NAMA                 varchar(255),
   primary key (STATUSUSULAN_ID)
);

alter table STATUSUSULAN comment 'status usulan ;
default ; diusulkan -> sedang direview';

/*==============================================================*/
/* Table: TAHUN_USULAN                                          */
/*==============================================================*/
alter table STATUSUSULAN change STATUSUSULAN_ID STATUSUSULAN_ID int AUTO_INCREMENT ;

create table TAHUN_USULAN
(
   TAHUN_USULAN_ID      int not null,
   TAHUN_USULAN         int not null,
   TAHUN_PELAKSANAAN    int not null,
   primary key (TAHUN_USULAN_ID)
);

/*==============================================================*/
/* Table: TIPEUSULAN                                            */
/*==============================================================*/
alter table TAHUN_USULAN change TAHUN_USULAN_ID TAHUN_USULAN_ID int AUTO_INCREMENT ;

create table TIPEUSULAN
(
   TIPE_USULAN_ID       int not null,
   NAMA                 varchar(255) not null,
   primary key (TIPE_USULAN_ID)
);

alter table TIPEUSULAN comment 'master - tipe usulan only contain 2 ... ';

/*==============================================================*/
/* Table: USERLOGIN                                             */
/*==============================================================*/
alter table TIPEUSULAN change TIPE_USULAN_ID TIPE_USULAN_ID int AUTO_INCREMENT ;

create table USERLOGIN
(
   USERLOGIN_ID         int not null,
   LOGIN                varchar(255) not null,
   PLAIN_PASSWORD       varchar(255),
   HASHED_PASSWORD      varchar(255),
   USER_LEVEL           int not null,
   primary key (USERLOGIN_ID)
);

alter table USERLOGIN comment 'pdm : unique (login)
user_level : for level 0 authenti';

/*==============================================================*/
/* Table: USERROLES                                             */
/*==============================================================*/
alter table USERLOGIN change USERLOGIN_ID USERLOGIN_ID int AUTO_INCREMENT ;

create table USERROLES
(
   USER_ROLES_ID        int not null,
   USERLOGIN_ID         int not null,
   USER_ROLE_TYPE_ID    int not null,
   primary key (USER_ROLES_ID)
);

/*==============================================================*/
/* Index: INDEX_4                                               */
/*==============================================================*/
create unique index INDEX_4 on USERROLES
(
   USERLOGIN_ID,
   USER_ROLE_TYPE_ID
);

/*==============================================================*/
/* Table: USER_DETAILS                                          */
/*==============================================================*/
alter table USERROLES change USER_ROLES_ID USER_ROLES_ID int AUTO_INCREMENT ;

create table USER_DETAILS
(
   USER_DETAIL_ID       int not null,
   USERLOGIN_ID         int not null,
   NAME                 varchar(255) not null,
   EMAIL                varchar(255) not null,
   MOBILE               varchar(255) not null,
   ADDRESS              text,
   primary key (USER_DETAIL_ID)
);

/*==============================================================*/
/* Table: USER_ROLE_TYPES                                       */
/*==============================================================*/
alter table USER_DETAILS change USER_DETAIL_ID USER_DETAIL_ID int AUTO_INCREMENT ;

create table USER_ROLE_TYPES
(
   USER_ROLE_TYPE_ID    int not null,
   NAME                 varchar(255) not null,
   primary key (USER_ROLE_TYPE_ID)
);

alter table USER_ROLE_TYPES comment 'master role type
UNIQ (name)
';

/*==============================================================*/
/* Table: USULAN                                                */
/*==============================================================*/
alter table USER_ROLE_TYPES change USER_ROLE_TYPE_ID USER_ROLE_TYPE_ID int AUTO_INCREMENT ;

create table USULAN
(
   USULAN_ID            int not null,
   PENGUSUL             int not null,
   USULANDIBUKA_ID      int not null,
   TANGGAL_USUL         date not null,
   JUDUL                varchar(255) not null,
   RINGKASAN            text not null,
   BIAYA                float,
   PATH_PROPOSAL        varchar(1024),
   REVIEWER1            int,
   NILAI_REVIEW_1       float,
   PATH_REVIEW_1        varchar(1024),
   REVIEWER2            int,
   NILAI_REVIEW_2       float,
   PATH_REVIEW_2        varchar(1024),
   STATUSUSULAN_ID      int not null,
   primary key (USULAN_ID)
);

alter table USULAN comment 'uniq index (usulan,tahun_usulan,tipe_usulan)

pa';

/*==============================================================*/
/* Index: INDEX_4                                               */
/*==============================================================*/
create unique index INDEX_4 on USULAN
(
   PENGUSUL,
   USULANDIBUKA_ID
);

/*==============================================================*/
/* Table: USULANDIBUKA                                          */
/*==============================================================*/
alter table USULAN change USULAN_ID USULAN_ID int AUTO_INCREMENT ;

create table USULANDIBUKA
(
   USULANDIBUKA_ID      int not null,
   TAHUN_USULAN_ID      int not null,
   TIPE_USULAN_ID       int not null,
   NAMA                 varchar(255) not null,
   TANGGAL_BUKA         date,
   BATASAKHIR           date not null,
   STATUS               int not null,
   primary key (USULANDIBUKA_ID)
);

/*==============================================================*/
/* Index: INDEX_4                                               */
/*==============================================================*/
create unique index INDEX_4 on USULANDIBUKA
(
   TIPE_USULAN_ID,
   TAHUN_USULAN_ID,
   NAMA
);














































alter table USULANDIBUKA change USULANDIBUKA_ID USULANDIBUKA_ID int AUTO_INCREMENT ;

alter table DOCUMENTUSULAN add constraint FK_RELATIONSHIP_10 foreign key (DOCUMENT_ID)
      references DOCUMENT (DOCUMENT_ID) on delete restrict on update restrict;
alter table DOCUMENTUSULAN add constraint FK_RELATIONSHIP_9 foreign key (USULAN_ID)
      references USULAN (USULAN_ID) on delete restrict on update restrict;
alter table DOKUMENUSULANDIBUKA add constraint FK_RELATIONSHIP_14 foreign key (USULANDIBUKA_ID)
      references USULANDIBUKA (USULANDIBUKA_ID) on delete restrict on update restrict;
alter table DOKUMENUSULANDIBUKA add constraint FK_RELATIONSHIP_15 foreign key (DOCUMENT_ID)
      references DOCUMENT (DOCUMENT_ID) on delete restrict on update restrict;
alter table USERROLES add constraint FK_RELATIONSHIP_5 foreign key (USER_ROLE_TYPE_ID)
      references USER_ROLE_TYPES (USER_ROLE_TYPE_ID) on delete restrict on update restrict;
alter table USERROLES add constraint FK_RELATIONSHIP_6 foreign key (USERLOGIN_ID)
      references USERLOGIN (USERLOGIN_ID) on delete restrict on update restrict;
alter table USER_DETAILS add constraint FK_RELATIONSHIP_8 foreign key (USERLOGIN_ID)
      references USERLOGIN (USERLOGIN_ID) on delete restrict on update restrict;
alter table USULAN add constraint FK_RELATIONSHIP_REVIEWER1 foreign key (REVIEWER1)
      references USERLOGIN (USERLOGIN_ID) on delete restrict on update restrict;
alter table USULAN add constraint FK_RELATIONSHIP_REVIEWER2 foreign key (REVIEWER2)
      references USERLOGIN (USERLOGIN_ID) on delete restrict on update restrict;
alter table USULAN add constraint FK_USULAN2PENGUSUL foreign key (PENGUSUL)
      references USERLOGIN (USERLOGIN_ID) on delete restrict on update restrict;
alter table USULAN add constraint FK_USULAN2STATUSSULAN foreign key (STATUSUSULAN_ID)
      references STATUSUSULAN (STATUSUSULAN_ID) on delete restrict on update restrict;
alter table USULAN add constraint FK_USULAN2USULANDIBUKA foreign key (USULANDIBUKA_ID)
      references USULANDIBUKA (USULANDIBUKA_ID) on delete restrict on update restrict;
alter table USULANDIBUKA add constraint FK_RELATIONSHIP_11 foreign key (TIPE_USULAN_ID)
      references TIPEUSULAN (TIPE_USULAN_ID) on delete restrict on update restrict;
alter table USULANDIBUKA add constraint FK_RELATIONSHIP_13 foreign key (TAHUN_USULAN_ID)
      references TAHUN_USULAN (TAHUN_USULAN_ID) on delete restrict on update restrict;
alter table USULANDIBUKA add constraint FK_STATUSUSULANDIBUKA foreign key (STATUS)
      references OPTIONSSTATUSUSULANDIBUKA (OPTIONSSTATUSUSULANDIBUKA_ID) on delete restrict on update restrict;
insert into userlogin (login,plain_password,user_level) values ('admin','admin132ab',100);
insert into userlogin (login,plain_password,user_level) values ('reviewer1','reviewer1',100);
insert into userlogin (login,plain_password,user_level) values ('joesmart','joesmart',100);
insert into userlogin (login,plain_password,user_level) values ('aril','aril',100);
insert into user_role_types (name) values ('Administror'),('Pengusul'),('Reviewer');
insert into userroles(user_role_type_id,userlogin_id) values (2,3);
insert into tipeusulan (nama) values ('Penelitian'), ('Pengabdian');
insert into `tahun_usulan` (tahun_usulan, tahun_pelaksanaan) values (2017,2018),(2018,2019);
insert into OptionsStatusUsulanDibuka (name) values ('terbuka'),('ditutup');
	insert into usulandibuka (TIPE_USULAN_ID,TAHUN_USULAN_ID,	NAMA,	BATASAKHIR,	TANGGAL_BUKA,	STATUS)	values 
              (1,1,	'TEST-PNBP TAHAP I',	'2017-12-22',	'2017-7-1',	1);
insert into statususulan (nama) values ('diusulkan'),('direview'),('ditolak'),('disetujui');
