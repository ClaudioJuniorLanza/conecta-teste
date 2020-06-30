<?php
header("Status: 404 Not Found");
die;

/**
	Recomenda��o:
	- Utilize o Google Fonts, sempre que poss�vel.
	
	Ferramentas �teis:
	- https://github.com/alexandre-imaginacom/Change-TTF-Embeddability
	- http://www.fontsquirrel.com/tools/webfont-generator
	- http://onlinefontconverter.com/font?id=p2

	Melhores maneiras de se utilizar uma WebFont de forma mais tranquila:
	1. Separe o arquivo TTF da fonte em quest�o;
	2. Acesse http://onlinefontconverter.com/
	2.1. Envie a fonte original (TTF)
	2.2. Converta para um formato edit�vel (SVG)
	2.3. Modifique o arquivo a fim de remover sua identifica��o
	2.4. Envie novamente o arquivo, e converta novamente para TTF
	2.5. Acesse "Propriedades" > "Detalhes", e confirme se:
		 - Authors:       Em branco
		 - Copyright:     None
		 - Embeddability: Installable
	3. Acesse http://www.fontsquirrel.com/tools/webfont-generator
	3.1. Fa�a upload do novo TTF
	3.2. Copie o stylesheet.css para o estilo.css, mais especificamente
		 a defini��o do @font-face
	3.3. A fonte definida naquele bloco estar� dispon�vel
	     para uso sempre que necess�rio.

	Padr�es sugeridos:
		a) Deixar em branco a tag <metatag></metatag>
		b) Colocar um md5(microtime()) no id
		c) Colocar um md5(microtime()) no nome da fonte
		d) Adicionar caracteres entre letras no nome do arquivo.
		   Por ex: "Maria" ==> "Miairiiia".
**/
