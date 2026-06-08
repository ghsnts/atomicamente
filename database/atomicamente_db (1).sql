-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: 08-Jun-2026 às 18:03
-- Versão do servidor: 5.7.25
-- versão do PHP: 7.1.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `atomicamente_db`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `alternatives`
--

CREATE TABLE `alternatives` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `letra` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto_alternativa` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `eh_correta` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `alternatives`
--

INSERT INTO `alternatives` (`id`, `question_id`, `letra`, `texto_alternativa`, `eh_correta`) VALUES
(21, 5, 'A', 'Formado por elétrons girando ao redor do núcleo.', 0),
(22, 5, 'B', 'Uma esfera maciça, indivisível e indestrutível.', 1),
(23, 5, 'C', 'Formado por prótons, nêutrons e elétrons.', 0),
(24, 5, 'D', 'Uma região de probabilidade eletrônica.', 0),
(25, 5, 'E', 'Constituído por um núcleo positivo envolvido por elétrons.', 0),
(26, 6, 'A', 'John Dalton', 0),
(27, 6, 'B', 'Ernest Rutherford', 0),
(28, 6, 'C', 'Niels Bohr', 0),
(29, 6, 'D', 'J. J. Thomson', 1),
(30, 6, 'E', 'James Chadwick', 0),
(31, 7, 'A', 'Modelo planetário', 0),
(32, 7, 'B', 'Modelo quântico', 0),
(33, 7, 'C', 'Modelo pudim de passas', 1),
(34, 7, 'D', 'Modelo nuclear', 0),
(35, 7, 'E', 'Modelo de camadas eletrônicas', 0),
(36, 8, 'A', 'O átomo é indivisível.', 0),
(37, 8, 'B', 'Os elétrons possuem carga positiva.', 0),
(38, 8, 'C', 'O átomo possui um núcleo pequeno e positivo.', 1),
(39, 8, 'D', 'Os nêutrons estão distribuídos na eletrosfera.', 0),
(40, 8, 'E', 'O átomo não possui espaços vazios.', 0),
(41, 9, 'A', 'Podem ocupar qualquer região ao redor do núcleo.', 0),
(42, 9, 'B', 'Não possuem energia.', 0),
(43, 9, 'C', 'Movem-se em órbitas de energia definida.', 1),
(44, 9, 'D', 'Estão localizados dentro do núcleo.', 0),
(45, 9, 'E', 'São partículas sem carga elétrica.', 0),
(46, 10, 'A', 'Próton', 0),
(47, 10, 'B', 'Nêutron', 0),
(48, 10, 'C', 'Pósitron', 0),
(49, 10, 'D', 'Elétron', 1),
(50, 10, 'E', 'Núcleo', 0),
(51, 11, 'A', 'Ao conjunto de prótons e nêutrons.', 0),
(52, 11, 'B', 'À região onde se encontra o núcleo.', 0),
(53, 11, 'C', 'À região onde estão os elétrons.', 1),
(54, 11, 'D', 'Ao espaço ocupado apenas pelos prótons.', 0),
(55, 11, 'E', 'À parte neutra do átomo.', 0),
(56, 12, 'A', 'Número de prótons menor que o de elétrons.', 0),
(57, 12, 'B', 'Número de nêutrons igual ao de elétrons.', 0),
(58, 12, 'C', 'Número de prótons igual ao de elétrons.', 0),
(59, 12, 'D', 'Apenas prótons e nêutrons.', 0),
(60, 12, 'E', 'Apenas elétrons.', 1),
(61, 13, 'A', 'Niels Bohr', 0),
(62, 13, 'B', 'Ernest Rutherford', 0),
(63, 13, 'C', 'J. J. Thomson', 0),
(64, 13, 'D', 'James Chadwick', 1),
(65, 13, 'E', 'John Dalton', 0),
(66, 14, 'A', 'Orbitais nucleares.', 0),
(67, 14, 'B', 'Camadas eletrônicas da eletrosfera.', 1),
(68, 14, 'C', 'Prótons do núcleo.', 0),
(69, 14, 'D', 'Nêutrons da eletrosfera.', 0),
(70, 14, 'E', 'Regiões sem energia definida.', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `aulas`
--

CREATE TABLE `aulas` (
  `id` int(11) NOT NULL,
  `topico_id` int(11) NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resumo` text COLLATE utf8mb4_unicode_ci,
  `ordem` int(11) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `aulas`
--

INSERT INTO `aulas` (`id`, `topico_id`, `titulo`, `video_url`, `resumo`, `ordem`) VALUES
(1, 1, 'Aula 1: Evolução dos Modelos (Dalton a Bohr)', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'Resumo sobre a evolução dos modelos atómicos e os postulados clássicos.', 1),
(2, 1, 'Aula sobre M&E', '', '', 1),
(3, 1, 'ssss', '', 'ss', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `fontes_aula`
--

CREATE TABLE `fontes_aula` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `fontes_aula`
--

INSERT INTO `fontes_aula` (`id`, `aula_id`, `descricao`, `link`) VALUES
(1, 1, 'Quimica feltre pag 122', 'https://quimicales.wordpress.com/wp-content/uploads/2013/09/quimica-feltre-vol-1.pdf');

-- --------------------------------------------------------

--
-- Estrutura da tabela `frentes`
--

CREATE TABLE `frentes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `frentes`
--

INSERT INTO `frentes` (`id`, `nome`, `slug`, `icone`) VALUES
(1, 'Química Geral', 'quimica-geral', '⚛️'),
(2, 'Físico-Química', 'fisico-quimica', '🔥'),
(3, 'Química Orgânica', 'quimica-organica', '🌿');

-- --------------------------------------------------------

--
-- Estrutura da tabela `materiais_apoio`
--

CREATE TABLE `materiais_apoio` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'pdf ou link',
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `medalhas`
--

CREATE TABLE `medalhas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regra_gatilho` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `medalhas`
--

INSERT INTO `medalhas` (`id`, `nome`, `descricao`, `icone`, `regra_gatilho`) VALUES
(1, 'Primeiro Passo', 'Iniciou a sua jornada e resolveu a primeira questão.', '👶', 'primeira_questao'),
(2, 'Semana Implacável', 'Alcançou 7 dias consecutivos de estudos.', '🔥', 'streak_7'),
(3, 'Atirador de Elite', 'Acertou 5 questões no mesmo dia.', '🎯', 'acertos_5_dia'),
(4, 'Maratonista', 'Resolveu 20 questões num único dia.', '🏃', 'resolvidas_20_dia'),
(5, 'Mestre da Consistência', 'Alcançou impressionantes 30 dias de ofensiva.', '👑', 'streak_30'),
(6, 'Veterano', 'Completou 100 exercícios na plataforma.', '🎖️', 'total_100');

-- --------------------------------------------------------

--
-- Estrutura da tabela `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `subtopic_id` int(11) NOT NULL,
  `aula_id` int(11) DEFAULT NULL,
  `enunciado` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `explicacao` text COLLATE utf8mb4_unicode_ci,
  `resolucao_comentada` text COLLATE utf8mb4_unicode_ci,
  `criado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `questions`
--

INSERT INTO `questions` (`id`, `subtopic_id`, `aula_id`, `enunciado`, `explicacao`, `resolucao_comentada`, `criado_por`) VALUES
(5, 1, NULL, 'Segundo o modelo atômico de Dalton, o átomo é:', NULL, NULL, NULL),
(6, 1, 1, 'A descoberta do elétron foi realizada por:', NULL, NULL, NULL),
(7, 1, NULL, 'O modelo atômico de Thomson ficou conhecido como:', NULL, NULL, NULL),
(8, 1, 1, 'A principal conclusão obtida por Rutherford através do experimento da lâmina de ouro foi que:', NULL, NULL, NULL),
(9, 1, 1, 'No modelo de Bohr, os elétrons:', NULL, NULL, NULL),
(10, 1, 1, 'Qual partícula subatômica possui carga elétrica negativa?', NULL, NULL, NULL),
(11, 1, 1, 'A eletrosfera corresponde:', NULL, NULL, NULL),
(12, 1, 1, 'Um átomo neutro possui:', NULL, NULL, NULL),
(13, 1, 1, 'Qual cientista descobriu o nêutron?', NULL, NULL, NULL),
(14, 1, 1, 'A distribuição eletrônica dos elétrons ocorre em:', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nome` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `roles`
--

INSERT INTO `roles` (`id`, `nome`) VALUES
(1, 'admin'),
(2, 'estudante');

-- --------------------------------------------------------

--
-- Estrutura da tabela `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `subjects`
--

INSERT INTO `subjects` (`id`, `nome`, `slug`) VALUES
(1, 'Química Geral', 'quimica-geral');

-- --------------------------------------------------------

--
-- Estrutura da tabela `subtopics`
--

CREATE TABLE `subtopics` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto_aula` text COLLATE utf8mb4_unicode_ci,
  `video_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fontes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `topicos`
--

CREATE TABLE `topicos` (
  `id` int(11) NOT NULL,
  `frente_id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `topicos`
--

INSERT INTO `topicos` (`id`, `frente_id`, `nome`, `slug`) VALUES
(1, 1, 'Modelos Atómicos & Eletrosfera', 'modelos-atomicos'),
(2, 1, 'Propriedades Periódicas', 'tabela-periodica'),
(3, 1, 'Ligações Químicas & Geometria', 'ligacoes-quimicas'),
(4, 1, 'Funções Inorgânicas', 'funcoes-inorganicas'),
(5, 1, 'Estequiometria & Cálculos', 'estequiometria'),
(6, 2, 'Soluções & Concentrações', 'solucoes'),
(7, 2, 'Termoquímica (Entalpia)', 'termoquimica'),
(8, 2, 'Cinética & Velocidade', 'cinetica-quimica'),
(9, 2, 'Equilíbrio Químico & pH', 'equilibrio-quimico'),
(10, 2, 'Eletroquímica (Pilhas/Eletrólise)', 'eletroquimica'),
(11, 3, 'Introdução & Cadeias Carbonadas', 'cadeias-carbonadas'),
(12, 3, 'Funções Orgânicas', 'funcoes-organicas'),
(13, 3, 'Isomeria Plana e Espacial', 'isomeria'),
(14, 3, 'Reações Orgânicas', 'reacoes-organicas'),
(16, 3, 'Polímeros & Bioquímica', 'polimeros-bioquimica');

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_diaria` int(11) DEFAULT '20',
  `frente_foco` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `streak` int(11) DEFAULT '0',
  `ultimo_estudo` date DEFAULT NULL,
  `email_verificado` tinyint(1) DEFAULT '0',
  `token_verificacao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `role_id`, `nome`, `email`, `password_hash`, `meta_diaria`, `frente_foco`, `streak`, `ultimo_estudo`, `email_verificado`, `token_verificacao`) VALUES
(1, 2, 'Gustavo', 'gustavo4.santos@alunos.ifsuldeminas.edu.br', '$2y$10$CKr8ubTjBJMNDvBsPn1KjOSLgq/slbJxpD1hoTZTRRoZcK3jhTaNi', 10, 'geral', 1, '2026-06-04', 0, NULL),
(2, 2, 'Teste', 'teste@alunos.ifsuldeminas.edu.br', '$2y$10$l7wVVsI1r4ZRgm9Bbqma5./2wJW2nKZ8ldrMSnIBFzADuuOp8uFEK', 20, NULL, 0, NULL, 0, NULL),
(3, 2, 'Gustavo', 'gustavoirwid@gmail.com', '$2y$10$gn/dlRnsDufTkRj0PzQo8uriEKmAmlSIhxg1naxJngFineLjpeQeC', 20, '', 0, NULL, 0, '1f4dd0161258e7b1bd5b459c454be985');

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_medalhas`
--

CREATE TABLE `user_medalhas` (
  `user_id` int(11) NOT NULL,
  `medalha_id` int(11) NOT NULL,
  `conquistada_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '1',
  `question_id` int(11) NOT NULL,
  `alternative_id` int(11) NOT NULL,
  `foi_correta` tinyint(1) NOT NULL DEFAULT '0',
  `respondido_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_correct` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `user_progress`
--

INSERT INTO `user_progress` (`id`, `user_id`, `question_id`, `alternative_id`, `foi_correta`, `respondido_em`, `is_correct`) VALUES
(1, 1, 6, 27, 0, '2026-06-07 23:22:03', 0),
(2, 1, 11, 52, 0, '2026-06-04 04:09:26', 0),
(3, 1, 7, 33, 1, '2026-06-04 04:09:26', 1),
(4, 1, 12, 56, 0, '2026-06-04 02:35:52', 0),
(5, 1, 5, 22, 1, '2026-06-07 23:21:52', 1),
(7, 1, 8, 39, 0, '2026-06-07 23:32:58', 0),
(8, 1, 13, 61, 0, '2026-06-04 04:09:26', 0),
(17, 1, 10, 49, 1, '2026-06-04 04:09:26', 1),
(18, 1, 9, 41, 0, '2026-06-04 04:09:26', 0),
(22, 1, 14, 70, 0, '2026-06-04 04:09:26', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alternatives`
--
ALTER TABLE `alternatives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topico_id` (`topico_id`);

--
-- Indexes for table `fontes_aula`
--
ALTER TABLE `fontes_aula`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aula_id` (`aula_id`);

--
-- Indexes for table `frentes`
--
ALTER TABLE `frentes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `materiais_apoio`
--
ALTER TABLE `materiais_apoio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aula_id` (`aula_id`);

--
-- Indexes for table `medalhas`
--
ALTER TABLE `medalhas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subtopic_id` (`subtopic_id`),
  ADD KEY `fk_aula` (`aula_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `subtopics`
--
ALTER TABLE `subtopics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `topicos`
--
ALTER TABLE `topicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `frente_id` (`frente_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_medalhas`
--
ALTER TABLE `user_medalhas`
  ADD PRIMARY KEY (`user_id`,`medalha_id`);

--
-- Indexes for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_question_unique` (`user_id`,`question_id`),
  ADD UNIQUE KEY `user_question_uni` (`user_id`,`question_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `alternative_id` (`alternative_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alternatives`
--
ALTER TABLE `alternatives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fontes_aula`
--
ALTER TABLE `fontes_aula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `frentes`
--
ALTER TABLE `frentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `materiais_apoio`
--
ALTER TABLE `materiais_apoio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medalhas`
--
ALTER TABLE `medalhas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subtopics`
--
ALTER TABLE `subtopics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topicos`
--
ALTER TABLE `topicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Limitadores para a tabela `alternatives`
--
ALTER TABLE `alternatives`
  ADD CONSTRAINT `alternatives_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `aulas`
--
ALTER TABLE `aulas`
  ADD CONSTRAINT `aulas_ibfk_1` FOREIGN KEY (`topico_id`) REFERENCES `topicos` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `fontes_aula`
--
ALTER TABLE `fontes_aula`
  ADD CONSTRAINT `fontes_aula_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `materiais_apoio`
--
ALTER TABLE `materiais_apoio`
  ADD CONSTRAINT `materiais_apoio_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `subtopics`
--
ALTER TABLE `subtopics`
  ADD CONSTRAINT `subtopics_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
