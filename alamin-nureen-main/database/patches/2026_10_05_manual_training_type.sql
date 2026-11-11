-- TrainHub Al Amin - allow Staff EDU to choose Simple or LMS manually.
-- Keeps the "Other target audience must be specified" validation.

DROP TRIGGER IF EXISTS `trg_course_target_type_insert_validate`;
DELIMITER ;;
CREATE TRIGGER `trg_course_target_type_insert_validate`
BEFORE INSERT ON `course`
FOR EACH ROW
BEGIN
    IF FIND_IN_SET('other', NEW.targetAudience) > 0
       AND (NEW.otherTargetAudience IS NULL OR TRIM(NEW.otherTargetAudience) = '') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Please specify other target audience.';
    END IF;
END ;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_course_target_type_update_validate`;
DELIMITER ;;
CREATE TRIGGER `trg_course_target_type_update_validate`
BEFORE UPDATE ON `course`
FOR EACH ROW
BEGIN
    IF FIND_IN_SET('other', NEW.targetAudience) > 0
       AND (NEW.otherTargetAudience IS NULL OR TRIM(NEW.otherTargetAudience) = '') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Please specify other target audience.';
    END IF;
END ;;
DELIMITER ;
