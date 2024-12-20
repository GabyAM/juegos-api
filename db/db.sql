-- MySQL Script generated by MySQL Workbench
-- lun 26 ago 2024 16:10:33
-- Model: New Model    Version: 1.0
-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema seminariophp
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema seminariophp
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `seminariophp` ;
USE `seminariophp` ;

-- -----------------------------------------------------
-- Table `seminariophp`.`usuario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `seminariophp`.`usuario` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre_usuario` VARCHAR(255) NOT NULL,
  `clave` VARCHAR(16) NOT NULL,
  `token` TEXT NULL,
  `vencimiento_token` DATETIME NULL,
  `es_admin` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `seminariophp`.`plataforma`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `seminariophp`.`plataforma` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `seminariophp`.`juego`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `seminariophp`.`juego` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(45) NOT NULL,
  `descripcion` LONGTEXT NOT NULL,
  `imagen` LONGTEXT NOT NULL,
  `clasificacion_edad` ENUM('ATP', '+13', '+18') NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `seminariophp`.`calificacion`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `seminariophp`.`calificacion` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `estrellas` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `juego_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_calificacion_usuario_idx` (`usuario_id` ASC) VISIBLE,
  INDEX `fk_calificacion_juego1_idx` (`juego_id` ASC) VISIBLE,
  CONSTRAINT `fk_calificacion_usuario`
    FOREIGN KEY (`usuario_id`)
    REFERENCES `seminariophp`.`usuario` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_calificacion_juego1`
    FOREIGN KEY (`juego_id`)
    REFERENCES `seminariophp`.`juego` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `seminariophp`.`soporte`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `seminariophp`.`soporte` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `juego_id` INT NOT NULL,
  `plataforma_id` INT NOT NULL,
  INDEX `fk_soportes_juego1_idx` (`juego_id` ASC) VISIBLE,
  INDEX `fk_soportes_plataforma1_idx` (`plataforma_id` ASC) VISIBLE,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_soportes_juego1`
    FOREIGN KEY (`juego_id`)
    REFERENCES `seminariophp`.`juego` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_soportes_plataforma1`
    FOREIGN KEY (`plataforma_id`)
    REFERENCES `seminariophp`.`plataforma` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `seminariophp`.`plataforma`
-- -----------------------------------------------------
START TRANSACTION;
USE `seminariophp`;
INSERT INTO `seminariophp`.`plataforma` (`id`, `nombre`) VALUES (DEFAULT, 'PS');
INSERT INTO `seminariophp`.`plataforma` (`id`, `nombre`) VALUES (DEFAULT, 'XBOX');
INSERT INTO `seminariophp`.`plataforma` (`id`, `nombre`) VALUES (DEFAULT, 'PC');
INSERT INTO `seminariophp`.`plataforma` (`id`, `nombre`) VALUES (DEFAULT, 'Android');
INSERT INTO `seminariophp`.`plataforma` (`id`, `nombre`) VALUES (DEFAULT, 'Otro');


INSERT INTO juego (nombre, descripcion, imagen, clasificacion_edad)
VALUES
  ('The Legend of Zelda: Breath of the Wild', 'Un juego de mundo abierto épico', 'imagen_base64_1', 'ATP'),
  ('Elden Ring', 'Un juego de rol de acción de mundo abierto', 'imagen_base64_2', '+18'),
  ('Cyberpunk 2077', 'Un RPG de mundo abierto ambientado en un futuro distópico', 'imagen_base64_3', '+18'),
  ('Minecraft', 'Un juego de construcción de mundo abierto', 'imagen_base64_4', 'ATP'),
  ('Red Dead Redemption 2', 'Un western de mundo abierto con una historia épica', 'imagen_base64_5', '+18'),
  ('Grand Theft Auto V', 'Un sandbox de mundo abierto con una historia criminal', 'imagen_base64_6', '+18'),
  ('The Witcher 3: Wild Hunt', 'Un RPG de fantasía épica con un mundo abierto enorme', 'imagen_base64_7', '+18'),
  ('God of War (2018)', 'Una reinvención de la saga God of War con una historia conmovedora', 'imagen_base64_8', '+18'),
  ('The Last of Us Part II', 'Una secuela emocionalmente intensa de un clásico moderno', 'imagen_base64_9', '+18'),
  ('Assassin''s Creed Valhalla', 'Un RPG de acción ambientado en la era vikinga', 'imagen_base64_10', '+18'),
  ('Hades', 'Un roguelike de acción con una estética mitológica', 'imagen_base64_11', '+18'),
  ('Stardew Valley', 'Un simulador de granja relajante y adictivo', 'imagen_base64_12', 'ATP'),
  ('Hollow Knight', 'Un metroidvania oscuro y desafiante', 'imagen_base64_13', '+13'),
  ('Among Us', 'Un juego multijugador social de deducción', 'imagen_base64_14', 'ATP'),
  ('Celeste', 'Un juego de plataformas desafiante con una historia conmovedora', 'imagen_base64_15', 'ATP'),
  ('Ori and the Will of the Wisps', 'Una aventura de plataformas metroidvania con una estética hermosa', 'imagen_base64_16', 'ATP');


COMMIT;