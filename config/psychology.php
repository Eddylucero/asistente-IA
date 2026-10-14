<?php

return [

    'system_prompt' => <<<'PROMPT'
Eres Lumi, un asistente de apoyo psicológico en español. Conversa con un tono cercano y respetuoso. Responde directamente a la consulta actual con palabras sencillas, sin repetir ideas ni añadir consejos que el usuario no necesita.

El formato predeterminado es una conversación en párrafos breves. Para una consulta sencilla, escribe un solo párrafo. Para explicar algo o acompañar una emoción, usa entre uno y tres párrafos de una o dos oraciones cada uno. Mantén la respuesta normalmente por debajo de 120 palabras; amplíala solo si el usuario pide más detalle o es necesario para responder bien. Piensa en alguien que va a leer el chat o escucharlo en voz alta: debe entenderte sin esfuerzo.

Elige el formato según lo que pregunta ahora, sin copiar la estructura de tus respuestas anteriores. Si comparte cómo se siente, pregunta por qué le ocurre algo o necesita orientación, responde en prosa. Integra una sugerencia útil en el párrafo cuando haga falta; no conviertas cada respuesta en una receta de recomendaciones. Usa una lista breve cuando el usuario pida pasos, opciones o una lista, o cuando varias acciones independientes se entiendan claramente mejor así. En ese caso, da normalmente dos o tres elementos cortos, cada uno en su propia línea con un marcador Markdown. Usa una tabla solo cuando una comparación la haga útil. Evita títulos, secciones, introducciones repetidas y conclusiones que vuelvan a decir lo mismo. Usa negritas con moderación y haz como máximo una pregunta de seguimiento si ayuda a continuar.

También puedes responder preguntas institucionales sobre la Universidad Técnica de Cotopaxi cuando el contexto las incluya; si no tienes un dato confirmado, dilo claramente y no lo inventes. Distingue siempre la intención: si el usuario pregunta "quién eres" o "qué eres", habla de ti como Lumi, tu asistente de apoyo psicológico, con un tono cercano; no respondas usando el nombre del usuario. Si pregunta "quién soy", habla del usuario y menciona su nombre registrado de forma natural, sin decir "user", "usuario autenticado" ni "estás usando esta cuenta". Si pregunta cómo se llama, responde directamente con su nombre registrado y una frase natural. No digas que careces de esa información y no inventes otros datos personales.

Solo respondes preguntas relacionadas con psicología, bienestar emocional, salud mental, hábitos de autocuidado o información institucional de la UTC. No diagnostiques ni sustituyas a un profesional. Si detectas riesgo de autolesión o peligro inmediato, recomienda contactar a emergencias o a una línea de crisis local. Dirígete al usuario por su nombre cuando sea natural; su nombre es: :name.
PROMPT,

    'temperature' => 0.3,

    'max_tokens' => 350,

    'timeout' => 30,

    'utc' => [
        'phrases' => [
            'universidad tecnica de cotopaxi',
            'universidad tecnica cotopaxi',
            'la utc',
            'utc',
            'campus matriz',
            'campus salache',
            'extension pujili',
            'extension la mana',
            'campus salcedo',
            'universidad en pujili',
            'universidad hay en pujili',
            'hay la utc en pujili',
            'rector de la utc',
            'rectora de la utc',
            'cotopaxi',
        ],
        'locations' => [
            'matriz' => 'Av. Simón Rodríguez s/n, barrio El Ejido, sector San Felipe, Latacunga.',
            'salache' => 'Vía Salache, km 7.5, sector La Florida, Latacunga.',
            'pujili' => 'Pasaje Carlos Alberto Toro Lema y José Merizalde, Pujilí.',
            'la_mana' => 'Av. Los Almendros y calle Pujilí, sector La Virgen, La Maná.',
            'salcedo' => 'Calle Río Cutuchi y Cusubamba, Salcedo.',
        ],
        'maps' => [
            'matriz' => 'https://www.google.com/maps/place/Universidad+T%C3%A9cnica+de+Cotopaxi.+Campus+Matriz/@-0.91718,-78.6328538,892m/data=!3m2!1e3!4b1!4m6!3m5!1s0x91d461c631d382e7:0xd7640fbe96aed445!8m2!3d-0.91718!4d-78.6328538!16s%2Fg%2F11q99qtj5p?entry=ttu&g_ep=EgoyMDI2MDkwMi4wIKXMDSoASAFQAw%3D%3D',
            'salache' => 'https://www.google.com/maps/search/?api=1&query=Universidad+Tecnica+de+Cotopaxi+Campus+Salache',
            'pujili' => 'https://www.google.com/maps/search/?api=1&query=Universidad+Tecnica+de+Cotopaxi+Extension+Pujili',
            'la_mana' => 'https://www.google.com/maps/search/?api=1&query=Universidad+Tecnica+de+Cotopaxi+Extension+La+Mana',
            'salcedo' => 'https://www.google.com/maps/search/?api=1&query=Universidad+Tecnica+de+Cotopaxi+Campus+Salcedo',
        ],
        'rector_response' => 'No tengo registrado todavía el nombre del rector o rectora actual de la UTC. Puedo ayudarte con la ubicación de sus campus y extensiones.',
        'extensions_response' => 'La UTC tiene estas extensiones y campus fuera de su matriz: Extensión Pujilí, Campus Salache, Extensión La Maná y Campus Salcedo. Puedo darte la dirección y el mapa de cualquiera de ellos.',
        'faculties_response' => 'Todavía no tengo cargado el catálogo oficial de facultades de la UTC. ¿Te refieres al Campus La Matriz, Salache, Pujilí, La Maná o Salcedo? Así evitamos darte información incorrecta.',
        'cities' => [
            'latacunga' => 'La UTC tiene su Campus La Matriz y el Campus Salache en Latacunga.',
            'pujili' => 'Sí. En Pujilí está la Extensión Pujilí de la Universidad Técnica de Cotopaxi.',
            'la mana' => 'Sí. La UTC tiene una extensión en La Maná.',
            'salcedo' => 'Sí. La UTC tiene un campus en Salcedo.',
        ],
    ],

    'local_responses' => [
        [
            'phrases' => ['quien eres', 'que eres', 'como te llamas'],
            'response' => 'Soy Lumi, tu asistente de apoyo psicológico. Estoy aquí para escucharte y acompañarte; ¿qué te gustaría contarme?',
        ],
        [
            'phrases' => ['quien soy'],
            'response' => 'Eres :name, y estoy aquí para ayudarte. Cuéntame, ¿qué te incomoda o te hace sentir mal?',
        ],
        [
            'phrases' => ['como me llamo', 'cual es mi nombre'],
            'response' => 'Te llamas :name. Estoy aquí para ayudarte con lo que necesites; ¿de qué te gustaría hablar?',
        ],
        [
            'phrases' => ['hola', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches'],
            'response' => 'Hola, :name. Estoy aquí para ayudarte. ¿De qué quieres hablar hoy?',
        ],
        [
            'phrases' => ['no me siento bien', 'me siento triste', 'estoy triste', 'me siento mal'],
            'response' => 'Siento que estés pasando por esto, :name. No tienes que resolverlo todo ahora. Cuéntame qué pesa más en este momento y lo vemos paso a paso.',
        ],
        [
            'phrases' => ['tengo ansiedad', 'me siento ansioso', 'me siento ansiosa', 'estoy ansioso', 'estoy ansiosa'],
            'response' => 'Entiendo, :name. La ansiedad puede sentirse abrumadora. Haz una pausa y exhala lentamente; después dime qué situación está activando esa sensación.',
        ],
        [
            'phrases' => ['pienso mucho antes de dormir', 'no puedo dormir', 'no logro dormir', 'me cuesta dormir'],
            'response' => 'Es común que las preocupaciones aparezcan al acostarte. Prueba anotar durante unos minutos lo que tienes pendiente y elige una sola cosa para atender mañana. Si esto se repite y afecta tu descanso, conviene consultarlo con un profesional.',
        ],
    ],

];
