SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `barista` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `barista` ;

-- -----------------------------------------------------
-- Table `barista`.`category`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `barista`.`category` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NOT NULL ,
  `image` VARCHAR(200) NULL ,
  `description` TEXT NULL ,
  `slug` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `barista`.`tag_type`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `barista`.`tag_type` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `barista`.`tag`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `barista`.`tag` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NOT NULL ,
  `slug` VARCHAR(100) NULL ,
  `tag_type_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_tag_tag_type` (`tag_type_id` ASC) ,
  CONSTRAINT `fk_tag_tag_type`
    FOREIGN KEY (`tag_type_id` )
    REFERENCES `barista`.`tag_type` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `barista`.`post`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `barista`.`post` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(200) NOT NULL ,
  `slug` VARCHAR(200) NOT NULL ,
  `subtitle` VARCHAR(200) NULL ,
  `summary` TEXT NULL ,
  `text` TEXT NULL ,
  `creation_date` DATE NOT NULL ,
  `public_date` DATE NULL ,
  `cover` VARCHAR(200) NULL ,
  `video` VARCHAR(400) NULL ,
  `map` VARCHAR(400) NULL ,
  `rating` FLOAT NULL ,
  `sort` INT NULL ,
  `visible` TINYINT(1) NULL DEFAULT 1 ,
  `starred` TINYINT(1) NULL DEFAULT 0 ,
  `category_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_post_category` (`category_id` ASC) ,
  CONSTRAINT `fk_post_category`
    FOREIGN KEY (`category_id` )
    REFERENCES `barista`.`category` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `barista`.`post_has_tag`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `barista`.`post_has_tag` (
  `post_id` INT NOT NULL ,
  `tag_id` INT NOT NULL ,
  PRIMARY KEY (`post_id`, `tag_id`) ,
  INDEX `fk_post_has_tag_tag` (`tag_id` ASC) ,
  INDEX `fk_post_has_tag_post` (`post_id` ASC) ,
  CONSTRAINT `fk_post_has_tag_post`
    FOREIGN KEY (`post_id` )
    REFERENCES `barista`.`post` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_post_has_tag_tag`
    FOREIGN KEY (`tag_id` )
    REFERENCES `barista`.`tag` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `barista`.`image`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `barista`.`image` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `src` VARCHAR(200) NOT NULL ,
  `title` VARCHAR(400) NULL ,
  `sort` INT NULL ,
  `post_id` INT NOT NULL ,
  PRIMARY KEY (`id`, `post_id`) ,
  INDEX `fk_image_post` (`post_id` ASC) ,
  CONSTRAINT `fk_image_post`
    FOREIGN KEY (`post_id` )
    REFERENCES `barista`.`post` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `barista`.`cf_type`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `barista`.`cf_type` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `barista`.`custom_field`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `barista`.`custom_field` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `value` VARCHAR(200) NOT NULL ,
  `title` VARCHAR(200) NULL ,
  `attributes` VARCHAR(100) NULL ,
  `post_id` INT NOT NULL ,
  `cf_type_id` INT NOT NULL ,
  PRIMARY KEY (`id`, `post_id`) ,
  INDEX `fk_custom_field_post` (`post_id` ASC) ,
  INDEX `fk_custom_field_cf_type` (`cf_type_id` ASC) ,
  CONSTRAINT `fk_custom_field_post`
    FOREIGN KEY (`post_id` )
    REFERENCES `barista`.`post` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_custom_field_cf_type`
    FOREIGN KEY (`cf_type_id` )
    REFERENCES `barista`.`cf_type` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `barista`.`file`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `barista`.`file` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `src` VARCHAR(200) NOT NULL ,
  `title` VARCHAR(400) NULL ,
  `sort` INT NULL ,
  `post_id` INT NOT NULL ,
  PRIMARY KEY (`id`, `post_id`) ,
  INDEX `fk_file_post` (`post_id` ASC) ,
  CONSTRAINT `fk_file_post`
    FOREIGN KEY (`post_id` )
    REFERENCES `barista`.`post` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `barista`.`user`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `barista`.`user` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NULL ,
  `username` VARCHAR(100) NULL ,
  `password` VARCHAR(100) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
