-- Database Backup
-- Generated: 2026-10-15 12:24:54

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `accounts`;

CREATE TABLE `accounts` (
  `employeeID` varchar(20) NOT NULL,
  `username` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `position` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`employeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `current` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(100) NOT NULL,
  `value` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `current` VALUES("1","School Year","2026-2027");

CREATE TABLE `designation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employeeID` varchar(20) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `sy` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=370 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `designation` VALUES("315","03-005","Campus Director","2026-2027");
INSERT INTO `designation` VALUES("316","09-017","CID Chief","2026-2027");
INSERT INTO `designation` VALUES("317","14-045","SSD Chief","2026-2027");
INSERT INTO `designation` VALUES("318","03-008","FAD Chief","2026-2027");
INSERT INTO `designation` VALUES("319","20-073","MIS Head","2026-2027");
INSERT INTO `designation` VALUES("320","17-066","AUH-Chemistry","2026-2027");
INSERT INTO `designation` VALUES("321","11-049","AUH-Physics","2026-2027");
INSERT INTO `designation` VALUES("322","04-018","AUH-Biology","2026-2027");
INSERT INTO `designation` VALUES("323","14-032","AUH-Computer Science","2026-2027");
INSERT INTO `designation` VALUES("324","12-002","AUH-Mathematics","2026-2027");
INSERT INTO `designation` VALUES("325","14-051","AUH-Integrated Science","2026-2027");
INSERT INTO `designation` VALUES("326","16-004","AUH-Technology","2026-2027");
INSERT INTO `designation` VALUES("327","11-015","AUH-English","2026-2027");
INSERT INTO `designation` VALUES("328","06-028","AUH-Filipino","2026-2027");
INSERT INTO `designation` VALUES("329","15-053","AUH-PEHM","2026-2027");
INSERT INTO `designation` VALUES("330","09-041","AUH-Social Science/Values Education","2026-2027");
INSERT INTO `designation` VALUES("331","06-028","Batch Adviser-G7","2026-2027");
INSERT INTO `designation` VALUES("332","03-003","Batch Adviser-G8","2026-2027");
INSERT INTO `designation` VALUES("333","20-073","Batch Adviser-G9","2026-2027");
INSERT INTO `designation` VALUES("334","16-033","Batch Adviser-G10","2026-2027");
INSERT INTO `designation` VALUES("335","03-007","Batch Adviser-G11","2026-2027");
INSERT INTO `designation` VALUES("336","21-080","Batch Adviser-G12","2026-2027");
INSERT INTO `designation` VALUES("337","04-022","Discipline Officer","2026-2027");
INSERT INTO `designation` VALUES("338","14-032","Discipline Officer","2026-2027");
INSERT INTO `designation` VALUES("339","08-038","Discipline Officer","2026-2027");
INSERT INTO `designation` VALUES("340","03-007","Discipline Committee Chairperson","2026-2027");
INSERT INTO `designation` VALUES("341","14-051","AUH-Research","2026-2027");
INSERT INTO `designation` VALUES("342","04-021","AUH-SCALE","2026-2027");
INSERT INTO `designation` VALUES("343","08-038","Homeroom Adviser G7-Diamond","2026-2027");
INSERT INTO `designation` VALUES("344","25-063","Homeroom Adviser G7-Emerald","2026-2027");
INSERT INTO `designation` VALUES("345","20-030","Homeroom Adviser G7-Sapphire","2026-2027");
INSERT INTO `designation` VALUES("346","11-031","Homeroom Adviser G7-Ruby","2026-2027");
INSERT INTO `designation` VALUES("347","16-060","Homeroom Adviser G8-Adelfa","2026-2027");
INSERT INTO `designation` VALUES("348","17-064","Homeroom Adviser G8-Camia","2026-2027");
INSERT INTO `designation` VALUES("349","24-043","Homeroom Adviser G8-Dahlia","2026-2027");
INSERT INTO `designation` VALUES("350","04-021","Homeroom Adviser G8-Sampaguita","2026-2027");
INSERT INTO `designation` VALUES("351","21-079","Homeroom Adviser G9-Cesium","2026-2027");
INSERT INTO `designation` VALUES("352","24-040","Homeroom Adviser G9-Lithium","2026-2027");
INSERT INTO `designation` VALUES("353","26-009","Homeroom Adviser G9-Beryllium","2026-2027");
INSERT INTO `designation` VALUES("354","23-036","Homeroom Adviser G9-Barium","2026-2027");
INSERT INTO `designation` VALUES("355","18-074","Homeroom Adviser G10-Electron","2026-2027");
INSERT INTO `designation` VALUES("356","21-019","Homeroom Adviser G10-Graviton","2026-2027");
INSERT INTO `designation` VALUES("357","21-065","Homeroom Adviser G10-Photon","2026-2027");
INSERT INTO `designation` VALUES("358","21-073","Homeroom Adviser G10-Boson","2026-2027");
INSERT INTO `designation` VALUES("359","24-053","Homeroom Adviser G11-Dalton","2026-2027");
INSERT INTO `designation` VALUES("360","05-026","Homeroom Adviser G11-Mendel","2026-2027");
INSERT INTO `designation` VALUES("361","21-078","Homeroom Adviser G11-Newton","2026-2027");
INSERT INTO `designation` VALUES("362","23-038","Homeroom Adviser G11-Curie","2026-2027");
INSERT INTO `designation` VALUES("363","23-037","Homeroom Adviser G12-Alpha","2026-2027");
INSERT INTO `designation` VALUES("364","16-055","Homeroom Adviser G12-Omega","2026-2027");
INSERT INTO `designation` VALUES("365","23-031","Homeroom Adviser G12-Delta","2026-2027");
INSERT INTO `designation` VALUES("366","11-015","Homeroom Adviser G12-Sigma","2026-2027");
INSERT INTO `designation` VALUES("367","10-046","ACIDAA","2026-2027");
INSERT INTO `designation` VALUES("368","15-027","ACIDSA","2026-2027");
INSERT INTO `designation` VALUES("369","11-015","Food Committee","2026-2027");


DROP TABLE IF EXISTS `guidance_referral_form`;

CREATE TABLE `guidance_referral_form` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `campus` varchar(255) NOT NULL,
  `student` varchar(255) NOT NULL,
  `grade_section` varchar(100) NOT NULL,
  `date_referred` date NOT NULL,
  `concern_academic` tinyint(1) NOT NULL DEFAULT 0,
  `concern_behavior` tinyint(1) NOT NULL DEFAULT 0,
  `concern_personal` tinyint(1) NOT NULL DEFAULT 0,
  `description` text NOT NULL,
  `intervention` text NOT NULL,
  `requires_followup` enum('Yes','No') NOT NULL,
  `behavior_depressed` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_hopelessness` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_crying` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_suicide` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_mood` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_emotional` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_withdrawal` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_excessive_activity` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_interaction` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_disruptive` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_appearance` tinyint(1) NOT NULL DEFAULT 0,
  `behavior_academic_decline` tinyint(1) NOT NULL DEFAULT 0,
  `other_behavior` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `referrer_email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


DROP TABLE IF EXISTS `office_designation`;

CREATE TABLE `office_designation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `officedesignation` varchar(200) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `office_designation` VALUES("1","Campus Director","active");
INSERT INTO `office_designation` VALUES("2","CID Chief","active");
INSERT INTO `office_designation` VALUES("3","SSD Chief","active");
INSERT INTO `office_designation` VALUES("4","FAD Chief","active");
INSERT INTO `office_designation` VALUES("5","MIS Head","active");
INSERT INTO `office_designation` VALUES("6","AUH-Chemistry","active");
INSERT INTO `office_designation` VALUES("7","AUH-Physics","active");
INSERT INTO `office_designation` VALUES("8","AUH-Biology","active");
INSERT INTO `office_designation` VALUES("9","AUH-Computer Science","active");
INSERT INTO `office_designation` VALUES("10","AUH-Mathematics","active");
INSERT INTO `office_designation` VALUES("11","AUH-Integrated Science","active");
INSERT INTO `office_designation` VALUES("12","AUH-Technology","active");
INSERT INTO `office_designation` VALUES("13","AUH-English","active");
INSERT INTO `office_designation` VALUES("14","AUH-Filipino","active");
INSERT INTO `office_designation` VALUES("15","AUH-PEHM","active");
INSERT INTO `office_designation` VALUES("16","AUH-Social Science/Values Education","active");
INSERT INTO `office_designation` VALUES("17","Batch Adviser-G7","active");
INSERT INTO `office_designation` VALUES("18","Batch Adviser-G8","active");
INSERT INTO `office_designation` VALUES("19","Batch Adviser-G9","active");
INSERT INTO `office_designation` VALUES("20","Batch Adviser-G10","active");
INSERT INTO `office_designation` VALUES("21","Batch Adviser-G11","active");
INSERT INTO `office_designation` VALUES("22","Batch Adviser-G12","active");
INSERT INTO `office_designation` VALUES("23","Homeroom Adviser G7-Diamond","active");
INSERT INTO `office_designation` VALUES("24","Homeroom Adviser G7-Emerald","active");
INSERT INTO `office_designation` VALUES("25","Homeroom Adviser G7-Sapphire","active");
INSERT INTO `office_designation` VALUES("26","Homeroom Adviser G7-Ruby","active");
INSERT INTO `office_designation` VALUES("27","Homeroom Adviser G8-Adelfa","active");
INSERT INTO `office_designation` VALUES("28","Homeroom Adviser G8-Camia","active");
INSERT INTO `office_designation` VALUES("29","Homeroom Adviser G8-Dahlia","active");
INSERT INTO `office_designation` VALUES("30","Homeroom Adviser G8-Sampaguita","active");
INSERT INTO `office_designation` VALUES("31","Homeroom Adviser G9-Cesium","active");
INSERT INTO `office_designation` VALUES("32","Homeroom Adviser G9-Lithium","active");
INSERT INTO `office_designation` VALUES("33","Homeroom Adviser G9-Beryllium","active");
INSERT INTO `office_designation` VALUES("34","Homeroom Adviser G10-Electron","active");
INSERT INTO `office_designation` VALUES("35","Homeroom Adviser G10-Graviton","active");
INSERT INTO `office_designation` VALUES("36","Homeroom Adviser G10-Photon","active");
INSERT INTO `office_designation` VALUES("37","Homeroom Adviser G11-Dalton","active");
INSERT INTO `office_designation` VALUES("38","Homeroom Adviser G11-Mendel","active");
INSERT INTO `office_designation` VALUES("39","Homeroom Adviser G11-Newton","active");
INSERT INTO `office_designation` VALUES("40","Homeroom Adviser G12-Alpha","active");
INSERT INTO `office_designation` VALUES("41","Homeroom Adviser G12-Omega","active");
INSERT INTO `office_designation` VALUES("42","Life Mentor G7-Diamond","active");
INSERT INTO `office_designation` VALUES("43","Life Mentor G7-Emerald","active");
INSERT INTO `office_designation` VALUES("44","Life Mentor G7-Sapphire","active");
INSERT INTO `office_designation` VALUES("45","Life Mentor G7-Ruby","active");
INSERT INTO `office_designation` VALUES("46","Life Mentor G8-Adelfa","active");
INSERT INTO `office_designation` VALUES("47","Life Mentor G8-Camia","active");
INSERT INTO `office_designation` VALUES("48","Life Mentor G8-Dahlia","active");
INSERT INTO `office_designation` VALUES("49","Life Mentor G8-Sampaguita","active");
INSERT INTO `office_designation` VALUES("50","Life Mentor G9-Cesium","active");
INSERT INTO `office_designation` VALUES("51","Life Mentor G9-Lithium","active");
INSERT INTO `office_designation` VALUES("52","Life Mentor G9-Beryllium","active");
INSERT INTO `office_designation` VALUES("53","Life Mentor G10-Electron","inactive");
INSERT INTO `office_designation` VALUES("54","Life Mentor G10-Graviton","inactive");
INSERT INTO `office_designation` VALUES("55","Life Mentor G10-Photon","inactive");
INSERT INTO `office_designation` VALUES("56","Life Mentor G11-Dalton","active");
INSERT INTO `office_designation` VALUES("57","Life Mentor G11-Mendel","active");
INSERT INTO `office_designation` VALUES("58","Life Mentor G11-Newton","active");
INSERT INTO `office_designation` VALUES("59","Life Mentor G12-Alpha","active");
INSERT INTO `office_designation` VALUES("60","Life Mentor G12-Delta","active");
INSERT INTO `office_designation` VALUES("61","Life Mentor G12-Omega","active");
INSERT INTO `office_designation` VALUES("62","Homeroom Coordinator","active");
INSERT INTO `office_designation` VALUES("63","Life Mentor Coordinator","active");
INSERT INTO `office_designation` VALUES("64","Discipline Officer","active");
INSERT INTO `office_designation` VALUES("65","Homeroom Adviser G12-Delta","active");
INSERT INTO `office_designation` VALUES("66","Discipline Committee Chairperson","active");
INSERT INTO `office_designation` VALUES("67","Budget Officer","active");
INSERT INTO `office_designation` VALUES("68","Cashier","active");
INSERT INTO `office_designation` VALUES("69","Procurement Officer","active");
INSERT INTO `office_designation` VALUES("70","Supply and Property Mgt. Officer","active");
INSERT INTO `office_designation` VALUES("71","HRMO","active");
INSERT INTO `office_designation` VALUES("72","Records Officer","active");
INSERT INTO `office_designation` VALUES("73","Document Controller","active");
INSERT INTO `office_designation` VALUES("74","QMSO","active");
INSERT INTO `office_designation` VALUES("75","Homeroom Adviser G9-Barium","active");
INSERT INTO `office_designation` VALUES("76","Homeroom Adviser G10-Boson","active");
INSERT INTO `office_designation` VALUES("77","Homeroom Adviser G11-Curie","active");
INSERT INTO `office_designation` VALUES("78","AUH-SCALE","active");
INSERT INTO `office_designation` VALUES("79","AUH-Research","active");
INSERT INTO `office_designation` VALUES("80","Dorm Manager","active");
INSERT INTO `office_designation` VALUES("81","Dorm Assistant","active");
INSERT INTO `office_designation` VALUES("82","Guard","active");
INSERT INTO `office_designation` VALUES("83","Homeroom Adviser G12-Sigma","active");
INSERT INTO `office_designation` VALUES("84","ACIDAA","active");
INSERT INTO `office_designation` VALUES("85","ACIDSA","active");
INSERT INTO `office_designation` VALUES("86","Food Committee","active");

CREATE TABLE `scilab_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scilabName` varchar(30) NOT NULL,
  `mainImagePath` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `availability` varchar(13) NOT NULL DEFAULT 'Available',
  `color` varchar(50) NOT NULL,
  `status` varchar(8) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `scilab_form_requests` (
  `statusScilabPersonnel` varchar(8) NOT NULL DEFAULT 'pending',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requesterEmployeeID` varchar(50) NOT NULL,
  `subjectAcademicUnit` varchar(50) NOT NULL,
  `controlNumber` int(100) NOT NULL,
  `control_equipment` varchar(100) DEFAULT NULL,
  `control_reagent` varchar(100) DEFAULT NULL,
  `control_permit` varchar(100) DEFAULT NULL,
  `control_reservation` varchar(100) DEFAULT NULL,
  `scilabName` varchar(255) NOT NULL,
  `teacherInCharge` varchar(100) NOT NULL,
  `sy` varchar(10) NOT NULL,
  `gradeLevel` int(2) NOT NULL,
  `sections` varchar(255) NOT NULL,
  `subject` varchar(25) NOT NULL,
  `subjectTopic` varchar(100) NOT NULL,
  `inclusiveDate` date NOT NULL,
  `inclusiveTime` varchar(255) NOT NULL,
  `dateRequested` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `feedback` varchar(999) NOT NULL,
  `supervisor_status` varchar(20) DEFAULT 'pending',
  `subject_teacher_status` varchar(20) DEFAULT 'pending',
  `lab_personnel_status` varchar(20) DEFAULT 'pending',
  `cid_chief_status` varchar(20) DEFAULT 'pending',
  `supervisor_approved_at` datetime DEFAULT NULL,
  `supervisor_approved_by` varchar(255) DEFAULT NULL,
  `subject_teacher_approved_at` datetime DEFAULT NULL,
  `subject_teacher_approved_by` varchar(255) DEFAULT NULL,
  `lab_personnel_approved_at` datetime DEFAULT NULL,
  `lab_personnel_approved_by` varchar(255) DEFAULT NULL,
  `cid_chief_approved_at` datetime DEFAULT NULL,
  `cid_chief_approved_by` varchar(255) DEFAULT NULL,
  `dateApproved` timestamp NULL DEFAULT NULL,
  `dateEndorsed` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `scilab_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productID` varchar(12) NOT NULL,
  `item` varchar(255) NOT NULL,
  `classification` varchar(20) NOT NULL,
  `location` varchar(100) NOT NULL,
  `laboratory` varchar(100) NOT NULL,
  `quantity` int(6) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `status` varchar(12) NOT NULL,
  `threshold_qty` int(6) DEFAULT NULL,
  `threshold_notified` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1235 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `scilab_material_requests` (
  `formID` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `description` varchar(50) NOT NULL,
  `issuedCondition` varchar(25) NOT NULL,
  `returnedCondition` varchar(25) NOT NULL,
  `returnedItemInspector` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `formID` (`formID`),
  CONSTRAINT `scilab_material_requests_ibfk_1` FOREIGN KEY (`formID`) REFERENCES `scilab_form_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `scilab_new_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` varchar(50) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `institution` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userID` (`userID`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


CREATE TABLE `scilab_students_involved` (
  `formID` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `formID` (`formID`),
  CONSTRAINT `scilab_students_involved_ibfk_1` FOREIGN KEY (`formID`) REFERENCES `scilab_form_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `section`;

CREATE TABLE `section` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade` varchar(5) NOT NULL,
  `section` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `section` VALUES("1","7","Diamond");
INSERT INTO `section` VALUES("2","7","Ruby");
INSERT INTO `section` VALUES("3","7","Emerald");
INSERT INTO `section` VALUES("4","7","Sapphire");
INSERT INTO `section` VALUES("5","8","Adelfa");
INSERT INTO `section` VALUES("6","8","Dahlia");
INSERT INTO `section` VALUES("7","8","Camia");
INSERT INTO `section` VALUES("8","8","Sampaguita");
INSERT INTO `section` VALUES("9","9","Lithium");
INSERT INTO `section` VALUES("10","9","Beryllium");
INSERT INTO `section` VALUES("11","9","Cesium");
INSERT INTO `section` VALUES("12","10","Graviton");
INSERT INTO `section` VALUES("13","10","Electron");
INSERT INTO `section` VALUES("14","10","Photon");
INSERT INTO `section` VALUES("15","11","Mendel");
INSERT INTO `section` VALUES("16","11","Dalton");
INSERT INTO `section` VALUES("17","11","Newton");
INSERT INTO `section` VALUES("18","12","Alpha");
INSERT INTO `section` VALUES("19","12","Delta");
INSERT INTO `section` VALUES("20","12","Omega");
INSERT INTO `section` VALUES("21","9","Barium");
INSERT INTO `section` VALUES("22","10","Boson");
INSERT INTO `section` VALUES("24","11","Curie");
INSERT INTO `section` VALUES("25","12","Sigma");



DROP TABLE IF EXISTS `student`;

CREATE TABLE `student` (
  `LRN` varchar(50) NOT NULL,
  `lastname` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `middlename` varchar(255) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `birthdate` varchar(20) NOT NULL,
  `entryData` varchar(25) NOT NULL,
  `scholarshipCategory` varchar(25) NOT NULL,
  `batch` varchar(5) NOT NULL,
  PRIMARY KEY (`LRN`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `student_directory` (
  `LRN` varchar(50) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gradeLevel` varchar(2) NOT NULL,
  `section` varchar(50) NOT NULL,
  `sy` varchar(10) NOT NULL,
  `studentEmail` varchar(100) NOT NULL,
  `studentPhoneNumber` varchar(20) NOT NULL,
  `homeAddress` varchar(255) NOT NULL,
  `permanentAddress` varchar(255) NOT NULL,
  `town` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `religion` varchar(100) NOT NULL,
  `father` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `fatherPhoneNumber` varchar(25) NOT NULL,
  `fatherEmail` varchar(100) NOT NULL,
  `fatherOccupation` varchar(100) NOT NULL,
  `fatherPlaceOfWork` varchar(255) NOT NULL,
  `mother` varchar(255) NOT NULL,
  `motherPhoneNumber` varchar(25) NOT NULL,
  `motherEmail` varchar(100) NOT NULL,
  `motherOccupation` varchar(100) NOT NULL,
  `motherPlaceOfWork` varchar(255) NOT NULL,
  `previousSchoolAttended` varchar(255) NOT NULL,
  `schoolType` varchar(10) NOT NULL,
  `schoolAddress` varchar(255) NOT NULL,
  `honors` varchar(100) NOT NULL,
  `guardian` varchar(255) NOT NULL,
  `guardianRelationship` varchar(100) NOT NULL,
  `guardianPhoneNumber` varchar(25) NOT NULL,
  `guardianEmail` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sd_lrn_sy` (`LRN`,`sy`),
  KEY `idx_sd_sy_grade_sec` (`sy`,`gradeLevel`,`section`)
) ENGINE=InnoDB AUTO_INCREMENT=7608 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `subject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subjectCode` varchar(50) NOT NULL,
  `subjectDescription` varchar(255) NOT NULL,
  `subjectUnit` varchar(10) NOT NULL,
  `subjectAcademicUnit` varchar(100) NOT NULL,
  `subjectGradeLevel` varchar(2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `subject` VALUES("1","IS 1","Integrated Science: Investigating Our Surrounding","1.7","Integrated Science","7","Inactive");
INSERT INTO `subject` VALUES("2","Math 1","Elementary Algebra","1.7","Mathematics","7","Inactive");
INSERT INTO `subject` VALUES("3","Computer Science 1","Foundations of Information and Communication Technology","1","Computer Science","7","Inactive");
INSERT INTO `subject` VALUES("4","English 1","Communication Arts 1 and Philippine Literature","1.3","English","7","Inactive");
INSERT INTO `subject` VALUES("5","Filipino 1","Kasanayan sa Komunikasyon (Wika at Panitikan)","1","Filipino","7","Inactive");
INSERT INTO `subject` VALUES("6","Social Science 1","Philippine History","1","Social Science/Values Education","7","Inactive");
INSERT INTO `subject` VALUES("7","Values Education 1","Foundation of Values Education","0.7","Social Science/Values Education","7","Inactive");
INSERT INTO `subject` VALUES("8","PEHM 1","PEHM 1","1","PEHM","7","Inactive");
INSERT INTO `subject` VALUES("9","ADTech 1","Art, Drafting and Technology: Basic Skills in Design and Technology","1","Technology","7","Inactive");
INSERT INTO `subject` VALUES("10","Earth Science 1","Earth Science 1","0.7","Physics","8","Inactive");
INSERT INTO `subject` VALUES("11","IS 2","Integrated Science 2: Exploring and Understanding the Interconnections of Science","2","Integrated Science","8","Inactive");
INSERT INTO `subject` VALUES("12","Computer Science 2","Introduction to Computational Thinking","1","Computer Science","8","Inactive");
INSERT INTO `subject` VALUES("13","English 2","Communication Arts 2 and Afro-Asian Literature","1.3","English","8","Inactive");
INSERT INTO `subject` VALUES("14","Filipino 2","Kasanayan sa Komunikasyon at Pagpapahalaga sa Noli Me Tangere","1","Filipino","8","Inactive");
INSERT INTO `subject` VALUES("15","Social Science 2","World History","1","Social Science/Values Education","8","Inactive");
INSERT INTO `subject` VALUES("16","Values Education 2","Foundation of Human Actions","0.7","Social Science/Values Education","8","Inactive");
INSERT INTO `subject` VALUES("17","PEHM 2","PEHM 2","1","PEHM","8","Inactive");
INSERT INTO `subject` VALUES("18","ADTech 2","An Intro. to the Design Process-Resistant Materials and Electronics","1","Technology","8","Inactive");
INSERT INTO `subject` VALUES("19","Math 2","Intermediate Algebra","1.7","Mathematics","8","Inactive");
INSERT INTO `subject` VALUES("20","Biology 1","Fundamentals of Biology 1","1","Biology","9","Inactive");
INSERT INTO `subject` VALUES("21","Chemistry 1","General Inorganic Chemistry 1","1","Chemistry","9","Inactive");
INSERT INTO `subject` VALUES("22","Physics 1","Fundamentals of Physics 1","1","Physics","9","Inactive");
INSERT INTO `subject` VALUES("23","Math 3","Mathematics 3","1","Mathematics","9","Inactive");
INSERT INTO `subject` VALUES("24","Computer Science 3","Client-Side Web Development","1","Computer Science","9","Inactive");
INSERT INTO `subject` VALUES("25","English 3","Communication Arts and English and American Literature","1","English","9","Inactive");
INSERT INTO `subject` VALUES("26","Filipino 3","Retorika, at Pasususri at Pagpapahalagang Pampanitikan","1","Filipino","9","Inactive");
INSERT INTO `subject` VALUES("27","Social Science 3","World History 2","1","Social Science/Values Education","9","Inactive");
INSERT INTO `subject` VALUES("28","PEHM 3","PEHM 3","1","PEHM","9","Inactive");
INSERT INTO `subject` VALUES("29","Statistics 1","Introduction to Statistics","1","Mathematics","9","Inactive");
INSERT INTO `subject` VALUES("30","Research 1","Science, Technology, Engineering, and Mathematics (STEM)","1","Research","10","active");
INSERT INTO `subject` VALUES("31","Biology 2","Fundamentals of Biology 2","1","Biology","10","active");
INSERT INTO `subject` VALUES("32","Chemistry 2","Introduction to Organic/Inorganic Chemistry 2","1","Chemistry","10","active");
INSERT INTO `subject` VALUES("33","Physics 2","Fundamentals of Physics 2","1","Physics","10","active");
INSERT INTO `subject` VALUES("34","Math 4","Mathematics 4","1.3","Mathematics","10","active");
INSERT INTO `subject` VALUES("35","Computer Science 4","Object-Oriented Programming","1","Computer Science","10","active");
INSERT INTO `subject` VALUES("36","English 4","Communication Arts 4 and World Literature","1","English","10","active");
INSERT INTO `subject` VALUES("37","Filipino 4","Kasaysayan at Pag-unlad ng Panitikang Filipino","1","Filipino","10","active");
INSERT INTO `subject` VALUES("38","Social Science 4","Phil. Government and Politics/Constitution","1","Social Science/Values Education","10","active");
INSERT INTO `subject` VALUES("39","PEHM 4","PEHM 4","1","PEHM","10","active");
INSERT INTO `subject` VALUES("40","Elective","","1","","10","active");
INSERT INTO `subject` VALUES("41","English 5","Effective Communication for Pre-University Students 1","1","English","11","active");
INSERT INTO `subject` VALUES("42","Filipino 5","Filipino sa Agham, Matematika at Teknolohiya","1","Filipino","11","active");
INSERT INTO `subject` VALUES("43","Math 5","Differential Calculus","1","Mathematics","11","active");
INSERT INTO `subject` VALUES("44","Research 2","Knowledge Integration, Application, and Extension","2","Research","11","active");
INSERT INTO `subject` VALUES("45","Science Core 1","Science Core 1","1.7","","11","active");
INSERT INTO `subject` VALUES("46","Social Science 5","Economics","1","Social Science/Values Education","11","active");
INSERT INTO `subject` VALUES("47","Elective","","1.7","","11","active");
INSERT INTO `subject` VALUES("48","English 6","Effective Communication for Pre-University Students 2","1","English","12","active");
INSERT INTO `subject` VALUES("49","Filipino 6","Pananaliksik sa Filipino","1","Filipino","12","active");
INSERT INTO `subject` VALUES("50","Math 6","Integrated Calculus and Linear Algebra","1","Mathematics","12","active");
INSERT INTO `subject` VALUES("51","Research 3","Research for a Sustainable Development","2","Research","12","active");
INSERT INTO `subject` VALUES("52","Science Core 2","Science Core 2","1.7","","12","active");
INSERT INTO `subject` VALUES("53","Social Science 6","Civic Engagement and Leadership","1","Social Science/Values Education","12","active");
INSERT INTO `subject` VALUES("54","Elective","","1.7","","12","active");
INSERT INTO `subject` VALUES("55","Values Education 3","Values Education 3","0.7","Social Science/Values Education","9","Inactive");
INSERT INTO `subject` VALUES("56","Values Education 4","Values Education 4","0.7","Social Science/Values Education","10","active");
INSERT INTO `subject` VALUES("57","CS Elective (Full Stack Web Dev)","Full Stack Web Development (Elective)","1","Computer Science","10","active");
INSERT INTO `subject` VALUES("58","Chem Elective (Envi. and Food Chem)","Environmental and Food Chemistry (Elective)","1","Chemistry","10","active");
INSERT INTO `subject` VALUES("59","CS Elective (Robotics)","Robotics (Elective)","1","Computer Science","10","active");
INSERT INTO `subject` VALUES("60","Physics Elective (Earth Sci.)","Earth Science (Elective)","1","Physics","10","active");
INSERT INTO `subject` VALUES("61","Biology Elective (PubHealth)","Public Health (Elective)","1","Biology","10","active");
INSERT INTO `subject` VALUES("62","Chem 3 Elective","Chemistry 3 (Elective)","1.7","Chemistry","11","active");
INSERT INTO `subject` VALUES("63","Bio 3 (Elective)","Biology 3 (Elective)","1.7","Biology","11","active");
INSERT INTO `subject` VALUES("64","Agri 1","Agriculture 1 (Elective)","1.7","Biology","11","active");
INSERT INTO `subject` VALUES("65","Computer Science 5","Data Structures and Algorithms (Elective)","1.7","Computer Science","11","active");
INSERT INTO `subject` VALUES("66","DMT","Design and Make Technologies (Elective)","1.7","Technology","11","active");
INSERT INTO `subject` VALUES("67","Engineering","Engineering (Elective)","1.7","Technology","11","active");
INSERT INTO `subject` VALUES("68","Science Core 1 (Biology)","Science Core 1 (Biology)","1.7","Biology","11","active");
INSERT INTO `subject` VALUES("69","Science Core 1 (Physics)","Science Core 1 (Physics)","1.7","Physics","11","active");
INSERT INTO `subject` VALUES("70","Science Core 1 (Chemistry)","Science Core 1 (Chemistry)","1.7","Chemistry","11","active");
INSERT INTO `subject` VALUES("71","Science Core 2 (Biology)","Science Core 2 (Biology)","1.7","Biology","12","active");
INSERT INTO `subject` VALUES("72","Science Core 2 (Physics)","Science Core 2 (Physics)","1.7","Physics","12","active");
INSERT INTO `subject` VALUES("73","Science Core 2 (Physics)","Science Core 2 (Chemistry)","1.7","Chemistry","12","active");
INSERT INTO `subject` VALUES("74","Chem 4 Elective","Chemistry 4 (Elective)","1.7","Chemistry","12","active");
INSERT INTO `subject` VALUES("75","Bio 4 Elective","Biology 4 (Elective)","1.7","Biology","12","active");
INSERT INTO `subject` VALUES("76","Agri 1","Agriculture 1 (Elective)","1.7","Biology","12","active");
INSERT INTO `subject` VALUES("77","Computer Science 5","Data Structures and Algorithms (Elective)","1.7","Computer Science","12","active");
INSERT INTO `subject` VALUES("78","-","-","1.7","Technology","12","active");
INSERT INTO `subject` VALUES("79","Engineering","Engineering (Elective)","1.7","Technology","12","active");
INSERT INTO `subject` VALUES("80","DMT","Design and Make Technologies (Elective)","1.7","Technology","12","active");
INSERT INTO `subject` VALUES("81","Technology Elective (DNF)","Design and Fabrication (Elective)","1","Technology","10","active");
INSERT INTO `subject` VALUES("82","SCALEg11","Service Creativity Action Leadership Enhancement Program (G11)","0","SCALE","11","active");
INSERT INTO `subject` VALUES("83","SCALEg12","Service Creativity Action Leadership Enhancement Program (G12)","0","SCALE","12","active");
INSERT INTO `subject` VALUES("84","Integrated Science","Introduction to Science and Earth Systems","1.7","Integrated Science","7","active");
INSERT INTO `subject` VALUES("85","Computer Science 1","Introduction to Computing","1","Computer Science","7","active");
INSERT INTO `subject` VALUES("86","Mathematics 1","Algebra 1","1.7","Mathematics","7","active");
INSERT INTO `subject` VALUES("87","ADTech 1","Art, Design, and Technology: Basic Principles and Processes","1","Technology","7","active");
INSERT INTO `subject` VALUES("88","English 1","Communication Arts 1","1.3","English","7","active");
INSERT INTO `subject` VALUES("89","Filipino 1","Wika at and Panitikang Pilipino","1","Filipino","7","active");
INSERT INTO `subject` VALUES("90","Social Science 1","Philippine History","1","Social Science/Values Education","7","active");
INSERT INTO `subject` VALUES("91","PEHM 1","PEHM 1","1","PEHM","7","active");
INSERT INTO `subject` VALUES("92","Values Education 1","Adolescent Living and Character Building","0.7","Social Science/Values Education","7","active");
INSERT INTO `subject` VALUES("93","Biology 1","Fundamentals of Biology 1","1","Biology","8","active");
INSERT INTO `subject` VALUES("94","Chemistry 1","General Chemistry 1","1","Chemistry","8","active");
INSERT INTO `subject` VALUES("95","Physics 1","Introduction to Physics","1","Physics","8","active");
INSERT INTO `subject` VALUES("96","Computer Science 2","Coding in a Connected World: Development of Computational Thinking Skills","1","Computer Science","8","active");
INSERT INTO `subject` VALUES("97","Mathematics 2A","Algebra 2","1","Mathematics","8","active");
INSERT INTO `subject` VALUES("98","Mathematics 2B","Geometry","1","Mathematics","8","active");
INSERT INTO `subject` VALUES("99","ADTech 2","Art, Design, and Technology: Resistant Materials and Electronics","1","Technology","8","active");
INSERT INTO `subject` VALUES("100","English 2","Communications Arts 2","1.3","English","8","active");
INSERT INTO `subject` VALUES("101","Filipino 2","Komunikasyon at ang Panitikang Pilipino","1","Filipino","8","active");
INSERT INTO `subject` VALUES("102","Social Science 2","World History","1","Social Science/Values Education","8","active");
INSERT INTO `subject` VALUES("103","PEHM 2","PEHM 2","1","PEHM","8","active");
INSERT INTO `subject` VALUES("104","Values Education 2","Toward Adolescent Wholeness","0.7","Social Science/Values Education","8","active");
INSERT INTO `subject` VALUES("105","Earth Science 1","Earth Systems, Energy, and Change","1","Physics","8","active");
INSERT INTO `subject` VALUES("106","Earth Science 1","Earth Systems, Energy, and Change","1","Physics","7","active");
INSERT INTO `subject` VALUES("107","Biology 2","Fundamentals of Biology 2","1.7","Biology","9","active");
INSERT INTO `subject` VALUES("108","Chemistry 2","General Chemistry 2","1.7","Chemistry","9","active");
INSERT INTO `subject` VALUES("109","Physics 2","Fundamentals of Physics 1","1.7","Physics","9","active");
INSERT INTO `subject` VALUES("110","Computer Science 3","Object-oriented Programming","1","Computer Science","9","active");
INSERT INTO `subject` VALUES("111","Mathematics 3","Algebra 3","1","Mathematics","9","active");
INSERT INTO `subject` VALUES("112","Statistics 1","Descriptive Statistics and Probability","1","Mathematics","9","active");
INSERT INTO `subject` VALUES("113","English 3","Communication Arts 3","1","English","9","active");
INSERT INTO `subject` VALUES("114","Filipino 3","Retorika, Pagsusuri at ang Noli Me Tangere","1","Filipino","9","active");
INSERT INTO `subject` VALUES("115","Social Science 3","Citizenship and the Economy","1","Social Science/Values Education","9","active");
INSERT INTO `subject` VALUES("116","PEHM 3","PEHM 3","1","PEHM","9","active");
INSERT INTO `subject` VALUES("117","Values Education 3","Values and Standards for a Meaningful Life","0.7","Social Science/Values Education","9","active");


DROP TABLE IF EXISTS `user_account`;

CREATE TABLE `user_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `studentID` (`studentID`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_account` VALUES("10","102202150016","neocarlo.malipot@irc.pshs.edu.ph","9fcbc01d9cbc96bfac7de30de3ccddd3");
INSERT INTO `user_account` VALUES("11","600009150293","labreaanicka.amano@irc.pshs.edu.ph","7cffa7c63e196c5649e8c7affcc4f341");
INSERT INTO `user_account` VALUES("12","410518150057","honjasper.flariz@irc.pshs.edu.ph","de8cdbbc021a427758c1b65666019851");
INSERT INTO `user_account` VALUES("13","136417150034","jianmarqo.llanes@irc.pshs.edu.ph","cfac92dbdbf606881bb12c5feab2e7aa");
INSERT INTO `user_account` VALUES("14","100105140037","glaizamae.oliveros@irc.pshs.edu.ph","3eb7ca1a97c9607b1c6d218e076f9513");
