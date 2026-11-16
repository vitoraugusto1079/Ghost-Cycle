import subprocess
import sys

try:
    import reportlab
except ImportError:
    subprocess.check_call([sys.executable, "-m", "pip", "install", "reportlab", "-q"])

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, KeepTogether, Image as RLImage

def gerar_pdf(caminho_saida):
    doc = SimpleDocTemplate(
        caminho_saida,
        pagesize=A4,
        leftMargin=14*mm,
        rightMargin=14*mm,
        topMargin=14*mm,
        bottomMargin=14*mm,
        title="Documentação do Desenvolvedor Full Stack - Ghost Cycle"
    )

    styles = getSampleStyleSheet()
    
    style_senai = ParagraphStyle(
        'SenaiBadge',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=18,
        leading=22,
        alignment=1,
        textColor=colors.HexColor('#DC2626')
    )
    style_title = ParagraphStyle(
        'DocTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=15,
        alignment=1,
        textColor=colors.HexColor('#0F172A'),
        spaceAfter=2
    )
    style_subtitle = ParagraphStyle(
        'DocSubTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=10,
        leading=13,
        alignment=1,
        textColor=colors.HexColor('#475569'),
        spaceAfter=10
    )
    style_sec = ParagraphStyle(
        'SecTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=11,
        leading=14,
        textColor=colors.HexColor('#4C1D95'),
        spaceBefore=10,
        spaceAfter=6
    )
    style_body = ParagraphStyle(
        'Body',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9.2,
        leading=13,
        textColor=colors.HexColor('#1E293B'),
        spaceAfter=6
    )
    style_cell = ParagraphStyle(
        'Cell',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.5,
        leading=11.5,
        textColor=colors.HexColor('#1E293B')
    )
    style_cell_bold = ParagraphStyle(
        'CellBold',
        parent=style_cell,
        fontName='Helvetica-Bold',
        textColor=colors.HexColor('#5B21B6')
    )
    style_th = ParagraphStyle(
        'CellTH',
        parent=style_cell,
        fontName='Helvetica-Bold',
        textColor=colors.white
    )

    story = []

    # Cabeçalho SENAI
    story.append(Paragraph("SENAI SÃO PAULO", style_senai))
    story.append(Paragraph("ATIVIDADE DE FIXAÇÃO E APROFUNDAMENTO — CASOS DE TESTE (TRIO)", style_title))
    story.append(Paragraph("PARTE DO DESENVOLVEDOR FULL STACK — PROJETO: GHOST CYCLE (GIFT CARDS & KEYS DE JOGOS)", style_subtitle))

    # Banner informativo com a Logo Oficial Ghost Cycle
    logo_img = RLImage(r"C:\xampp\htdocs\ghostcycle\assets\img\logo-icon.png", width=22*mm, height=17*mm)
    banner_data = [[
        logo_img,
        Paragraph("<b>PROJETO: GHOST CYCLE DIGITAL STORE</b><br/>Inspirado em <b>Rei dos Coins</b> e <b>HowStore</b> | Banco MySQL: <b>ghost_cycle</b> | Pasta: <b>http://10.134.227.224/ghostcycle/</b>", 
                  ParagraphStyle('BannerText', parent=style_cell, textColor=colors.white, fontSize=9.2, leading=13))
    ]]
    banner_table = Table(banner_data, colWidths=[26*mm, 156*mm])
    banner_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor('#090A14')),
        ('PADDING', (0, 0), (-1, -1), 7),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('ALIGN', (0, 0), (0, 0), 'CENTER'),
    ]))
    story.append(banner_table)
    story.append(Spacer(1, 6))

    # 1. Papéis
    story.append(Paragraph("1. PAPÉIS NO DESENVOLVIMENTO DO SISTEMA (TRIO)", style_sec))
    papeis_data = [
        [Paragraph("Papel", style_th), Paragraph("Integrante", style_th), Paragraph("Responsabilidade na Atividade", style_th)],
        [Paragraph("<b>Desenvolvedor Full Stack</b>", style_cell_bold), Paragraph("Desenvolvedor Full Stack (Autor desta especificação)", style_cell), Paragraph("Constrói o banco de dados (ghost_cycle), a estrutura de pastas, as 7 telas do site Ghost Cycle e escreve o resumo do sistema e os Requisitos Funcionais (RFs) de cada tela.", style_cell)],
        [Paragraph("<b>QA Sênior</b>", style_cell_bold), Paragraph("[Preencher Nome - QA Sênior]", style_cell), Paragraph("Escreve pelo menos 5 casos de teste para cada uma das 7 telas (mín. 35 CTs) + 3 casos de banco (Total: 38 CTs) e cria a tabela vinculando cada CT por RF.", style_cell)],
        [Paragraph("<b>QA Júnior</b>", style_cell_bold), Paragraph("[Preencher Nome - QA Júnior]", style_cell), Paragraph("Executa os testes na máquina do desenvolvedor via IP (http://10.134.227.224/ghostcycle) e preenche resultado obtido, deferido/indeferido, comentário e print.", style_cell)],
    ]
    t_papeis = Table(papeis_data, colWidths=[42*mm, 52*mm, 88*mm])
    t_papeis.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#1E293B')),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#CBD5E1')),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('PADDING', (0, 0), (-1, -1), 5),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor('#F8FAFC')])
    ]))
    story.append(t_papeis)

    # 2. O que é o sistema
    story.append(Paragraph("2. O QUE É O SISTEMA (RESUMO DO DESENVOLVEDOR FULL STACK)", style_sec))
    story.append(Paragraph(
        "O <b>Ghost Cycle</b> é uma plataforma web Full Stack de e-commerce gamer voltada para a venda de <b>Gift Cards (Cartões-Presente), Moedas VIP (Top-Up) e CD-Keys de Jogos para PC e Consoles</b>, inspirada diretamente nos portais <b>Rei dos Coins</b> e <b>HowStore</b>. "
        "O sistema foi construído em PHP 8 com PDO, MySQL/MariaDB (banco <b>ghost_cycle</b>), HTML5, CSS3 Cyber-Gamer e JavaScript.",
        style_body
    ))
    story.append(Paragraph(
        "A plataforma gerencia diferentes estados de sessão (Visitante Não Logado, Usuário Comum Logado e Administrador Master), conta com carteira digital integrada (<b>Ghost Wallet</b>) com recarga via PIX, cálculo automático de <b>Cashback</b> a cada pedido, emissão instantânea de PINs/CD-Keys exclusivas na tela de execução (Carrinho/Checkout) e Biblioteca de Keys para consulta vitalícia.",
        style_body
    ))
    story.append(Paragraph(
        "<b>Credenciais Prontas no Banco para os QAs:</b> Admin: <b>admin@ghostcycle.com</b> (Senha: 123456) | Cliente Comum: <b>cliente@ghostcycle.com</b> (Senha: 123456 / Palavra de Segurança: eldenring / CPF: 111.222.333-44) | Cliente 2: <b>mariana@ghostcycle.com</b> (Senha: 123456 / Palavra: valorant).",
        style_body
    ))

    # 3. Requisitos Funcionais por Tela
    story.append(Paragraph("3. REQUISITOS FUNCIONAIS (RFs) DE CADA UMA DAS 7 TELAS (RF-01 A RF-31)", style_sec))

    telas = [
        ("TELA 1 — Home (Com diferença entre Logado e Não Logado) | Arquivo: index.php", [
            ("RF-01", "Modo Visitante (Não Logado)", "Exibe o banner de visitante convidando ao cadastro gratuito, oculta a carteira Ghost Wallet e o ícone de carrinho no topo e bloqueia os botões dos cards exibindo 'Faça login para comprar'."),
            ("RF-02", "Modo Conectado (Usuário Logado)", "Exibe o painel personalizado Membro Ghost VIP com Nickname, Saldo atual da Ghost Wallet, ícone de Carrinho no cabeçalho, últimas Keys liberadas da conta e libera o botão 'Comprar agora'."),
            ("RF-03", "Vitrine de Gift Cards e Keys", "Lista os 46 produtos da tabela 'produtos' em cards com capa (ou ícone da plataforma, se não houver imagem), categoria, gênero, plataforma, preço, selo de desconto (%), cashback e estoque. Produtos sem estoque exibem 'Indisponível'."),
            ("RF-04", "Categorias e Filtros Laterais (estilo Nuuvem)", "Barra de categorias no topo (Tudo, Gift Cards, Keys de Jogos, Moedas & Top-Up, Assinaturas, Ofertas) e barra lateral com filtros combináveis por Categoria, Plataforma, Gênero/Tipo, Faixa de preço, 'Desconto de 10% ou mais' e 'Somente com estoque'. Cada opção exibe a quantidade de produtos."),
            ("RF-05", "Busca Textual Rápida", "Permite pesquisar por nome, plataforma, gênero ou descrição (ex.: Steam, Valorant, Witcher). Sem resultados, exibe a mensagem 'Nenhum produto encontrado'."),
            ("RF-29", "Filtros Ativos e Contador de Resultados", "Exibe a quantidade de produtos encontrados e os filtros aplicados como etiquetas removíveis; o botão 'Limpar filtros' restaura o catálogo completo."),
            ("RF-30", "Ordenação do Catálogo", "Permite ordenar por Relevância, Mais vendidos, Maiores descontos, Menor preço, Maior preço, Nome (A-Z) e Lançamentos."),
            ("RF-31", "Paginação", "Exibe 12 produtos por página com navegação entre páginas, mantendo os filtros e a ordenação escolhidos."),
        ]),
        ("TELA 2 — Tela de Cadastro | Arquivo: cadastro.php", [
            ("RF-06", "Formulário de Cadastro Gamer", "Permite cadastrar nova conta informando Nome Completo, Nickname Gamer, E-mail, CPF, Telefone, Palavra de Segurança, Senha e Confirmação de Senha."),
            ("RF-07", "Validação de Campos e Senha", "Impede o cadastro se houver campos obrigatórios vazios, e-mail inválido, senha menor que 6 caracteres ou confirmação de senha divergente."),
            ("RF-08", "Verificação de Duplicidade", "Impede o cadastro caso o E-mail ou o CPF já esteja registrado na tabela 'usuarios' do banco de dados."),
            ("RF-09", "Hash de Senha e Criação de Carteira", "Salva a senha criptografada (bcrypt), define o perfil como 'comum' e inicializa a Ghost Wallet do usuário com saldo R$ 0,00 ativo."),
        ]),
        ("TELA 3 — Tela de Login (Caminhos: Usuário Comum e Administrador) | Arquivo: login.php", [
            ("RF-10", "Caminho de Login: Usuário Comum", "Autentica contas do tipo 'comum' ativas por e-mail e senha, cria a sessão e redireciona automaticamente para a Home Logada ou tela de compra."),
            ("RF-11", "Caminho de Login: Administrador", "Autentica contas do tipo 'admin' por e-mail e senha, cria sessão com privilégio master e redireciona automaticamente para a Área do Administrador (admin.php)."),
            ("RF-12", "Identificação Automática de Perfil", "Identifica automaticamente o nível de acesso ('comum' ou 'admin') a partir do cadastro no banco de dados no momento do login, sem exigir seleção manual."),
            ("RF-13", "Validação de Credenciais e Suspensão", "Exibe erro específico para e-mail não encontrado, senha incorreta ou tentativa de acesso em conta com status 'suspenso'."),
        ]),
        ("TELA 4 — Tela de Perfil (Com Edição de Informações) | Arquivo: perfil.php", [
            ("RF-14", "Visualização de Perfil e Indicadores", "Exibe dados da conta logada, saldo atual na Ghost Wallet, total de pedidos concluídos e total de Cashback acumulado."),
            ("RF-15", "Edição de Informações Cadastrais", "Permite ao usuário editar e persistir no banco seu Nome Completo, Nickname Gamer, E-mail, Telefone, Palavra de Segurança e Avatar."),
            ("RF-16", "Troca Opcional de Senha no Perfil", "Atualiza a senha caso o usuário informe uma nova senha (mín. 6 caracteres) ou preserva a senha atual caso o campo fique em branco."),
            ("RF-17", "Recarga Rápida na Ghost Wallet", "Permite adicionar saldo (R$ 50, R$ 100, R$ 250 ou R$ 500) via PIX Simulado diretamente na carteira do usuário no banco de dados."),
        ]),
        ("TELA 5 — Tela de Execução: Compra & Cofre de Keys (Só abre logado) | Arquivo: comprar.php", [
            ("RF-18", "Acesso Exclusivo para Logados", "A tela de execução só abre se o usuário estiver logado; visitantes que acessarem a URL são redirecionados para login.php."),
            ("RF-19", "Checkout e Geração Instantânea de Key", "Processa a compra do Gift Card/Key escolhido (1 a 10 unidades) via Ghost Wallet, PIX ou Cartão e exibe imediatamente o PIN/CD-Key gerado."),
            ("RF-20", "Débito de Saldo, Estoque e Cashback", "Valida saldo suficiente na Ghost Wallet, abate a quantidade do estoque do produto e soma automaticamente o Cashback ganho ao saldo do cliente."),
            ("RF-21", "Cofre de Keys e Histórico de Pedidos", "Lista todos os pedidos realizados pelo usuário com código da transação, valor, cashback, data e botão para copiar a Key."),
        ]),
        ("TELA 6 — Tela de Redefinição de Senha | Arquivo: redefinir_senha.php", [
            ("RF-22", "Validação por E-mail + Palavra ou CPF", "Valida a identidade do titular exigindo o E-mail cadastrado combinado com a Palavra de Segurança ou o CPF registrado no banco."),
            ("RF-23", "Gravação da Nova Senha e Log no Banco", "Atualiza o hash da nova senha na tabela 'usuarios' e insere registro de auditoria na tabela 'logs_redefinicao'."),
            ("RF-24", "Bloqueio de Redefinição Inválida", "Recusa a alteração e exibe mensagem de erro se o e-mail não existir, se a palavra/CPF não conferir ou se as senhas não coincidirem."),
        ]),
        ("TELA 7 — Área do Administrador (Lista, Edita e Exclui Usuários) | Arquivo: admin.php", [
            ("RF-25", "Bloqueio Estrito de Usuário Comum", "O usuário comum NÃO entra nesta tela. Tentativas de acesso por usuário comum ou visitante são bloqueadas e redirecionadas."),
            ("RF-26", "Listagem e Filtro de Usuários", "Lista todos os usuários do banco (ID, Nome, Nick, E-mail, CPF, Tipo, Saldo e Status) e permite filtrar por pesquisa textual."),
            ("RF-27", "Edição Completa pelo Administrador", "Permite ao administrador editar Nome, Nick, E-mail, CPF, Permissão (comum/admin), Saldo da Ghost Wallet e Status (ativo/suspenso) de qualquer conta."),
            ("RF-28", "Exclusão de Usuário com Proteção", "Permite ao administrador excluir usuários do banco de dados, bloqueando apenas a autoexclusão da própria conta administrativa logada."),
        ]),
    ]

    for titulo_tela, rfs in telas:
        rows = [[Paragraph(titulo_tela, style_th), "", ""]]
        rows.append([Paragraph("<b>Código</b>", style_th), Paragraph("<b>Requisito Funcional</b>", style_th), Paragraph("<b>Descrição / Regra do Sistema</b>", style_th)])
        for cod, nome, desc in rfs:
            rows.append([
                Paragraph(cod, style_cell_bold),
                Paragraph(f"<b>{nome}</b>", style_cell),
                Paragraph(desc, style_cell)
            ])
        t = Table(rows, colWidths=[18*mm, 46*mm, 118*mm])
        t.setStyle(TableStyle([
            ('SPAN', (0, 0), (-1, 0)),
            ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#311042')),
            ('BACKGROUND', (0, 1), (-1, 1), colors.HexColor('#1E293B')),
            ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#CBD5E1')),
            ('VALIGN', (0, 0), (-1, -1), 'TOP'),
            ('PADDING', (0, 0), (-1, -1), 4.5),
            ('ROWBACKGROUNDS', (0, 2), (-1, -1), [colors.white, colors.HexColor('#F8FAFC')])
        ]))
        story.append(KeepTogether([t, Spacer(1, 7)]))

    doc.build(story)

if __name__ == "__main__":
    gerar_pdf(r"C:\xampp\htdocs\ghostcycle\Documentacao_Dev_FullStack_Ghost_Cycle.pdf")
    gerar_pdf(r"C:\xampp\htdocs\ghostcycle\docs\Documentacao_Dev_FullStack_Ghost_Cycle.pdf")
    print("PDF GERADO COM SUCESSO!")
