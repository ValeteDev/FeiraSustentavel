CREATE DATABASE feira_sustentavel;
USE feira_sustentavel;

CREATE TABLE Usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    tipo ENUM('doador', 'familia') NOT NULL,
    telefone VARCHAR(20),
    endereco TEXT,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Doacao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doador_id INT NOT NULL,
    data DATE NOT NULL,
    status ENUM('disponivel', 'reservado', 'entregue') DEFAULT 'disponivel',
    observacoes TEXT,
    FOREIGN KEY (doador_id) REFERENCES Usuario(id)
);

CREATE TABLE Alimento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doacao_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    quantidade INT NOT NULL,
    validade DATE,
    categoria ENUM('fruta', 'verdura', 'legume', 'outro') NOT NULL,
    FOREIGN KEY (doacao_id) REFERENCES Doacao(id) ON DELETE CASCADE
);

CREATE TABLE Familia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    num_membros INT NOT NULL,
    renda_mensal DECIMAL(10,2),
    situacao ENUM('baixa_renda', 'vulnerabilidade', 'outra') NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES Usuario(id)
);

CREATE TABLE Reserva (
    id INT PRIMARY KEY AUTO_INCREMENT,
    familia_id INT NOT NULL,
    doacao_id INT NOT NULL,
    data DATE NOT NULL,
    status ENUM('pendente', 'confirmada', 'cancelada', 'retirada') DEFAULT 'pendente',
    FOREIGN KEY (familia_id) REFERENCES Familia(id),
    FOREIGN KEY (doacao_id) REFERENCES Doacao(id)
);

CREATE TABLE Detrato (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    motivo TEXT NOT NULL,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES Usuario(id)
);