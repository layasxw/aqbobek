import mysql.connector
from flask import Flask, jsonify
from openai import OpenAI

app = Flask(__name__)

db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="aqbobek"
)

client = OpenAI(
    api_key="",
    base_url="https:api.groq.com/openai/v1"
)

@app.route('/advice/<int:student_id>/<int:subject_id>')
def advice(student_id, subject_id):
    cursor = db.cursor()

    cursor.execute("""
        SELECT score FROM grades 
        WHERE student_id = %s AND subject_id = %s
        ORDER BY date DESC
        LIMIT 5
    """, (student_id, subject_id))

    rows = cursor.fetchall()
    grades = [row[0] for row in rows]

    if not grades:
        return jsonify({"error": "Нет данных по оценкам"}), 404

    avg = sum(grades) / len(grades)
    min_score = min(grades)

    trend = grades[0] - grades[-1]

    if trend > 0:
        trend_text = f"успеваемость улучшилась на {trend} баллов"
    elif trend < 0:
        trend_text = f"успеваемость снизилась на {abs(trend)} баллов"
    else:
        trend_text = "успеваемость стабильна"

    prompt = f"""
Ты помощник для школьной платформы.
Данные ученика по предмету:
- Последние оценки: {grades}
- Средний балл: {avg:.1f}
- Минимальная оценка: {min_score}
- Тренд: {trend_text}

Сформулируй:
1. Короткий вывод (1 предложение)
2. 2-3 конкретных совета ученику
3. Тон: поддерживающий, простой, понятный школьнику
Ответ на русском языке, максимум 80 слов.
"""

    try:
        response = client.chat.completions.create(
            model="llama-3.3-70b-versatile",  # можешь поменять
            messages=[
                {"role": "system", "content": "Ты даешь краткие и полезные учебные советы."},
                {"role": "user", "content": prompt}
            ],
            temperature=0.5,
            max_tokens=200
        )

        advice_text = response.choices[0].message.content

        return jsonify({
            "student_id": student_id,
            "subject_id": subject_id,
            "grades": grades,
            "average": round(avg, 1),
            "min_score": min_score,
            "trend": trend,
            "advice": advice_text
        })

    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(port=5000, debug=True)