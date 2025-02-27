from flask import Flask, request, jsonify
import json 

app = Flask(__name__)

@app.route('/process_csv', methods=['POST'])
def process_csv():
    if 'file' not in request.files:
        return jsonify({"error": "Nenhum arquivo enviado"}), 400
    
    file = request.files['file']
    
    try:
        file_content = file.stream.read().decode("utf-8")
        lines = file_content.splitlines()

        # ignorar linhas inválidas
        if 'Status do Arquivo: Final' in lines[0]:
            lines = lines[1:] 
        
        header = lines[0].split(';')
        lines = lines[1:] 

        desired_columns = request.form.get('desired_columns')
        if desired_columns:
            desired_columns = json.loads(desired_columns) 
        else:
            return jsonify({"error": "Nenhuma coluna desejada fornecida"}), 400

        page = int(request.form.get('page', 1))
        per_page = int(request.form.get('per_page', 20))

        offset = (page - 1) * per_page
        paginated_lines = lines[offset:offset + per_page]

        response_data = []
        for line in paginated_lines:
            row = line.split(';')
            if len(row) > 0:
                row_assoc = {}
                for i, value in enumerate(row):
                    column_name = header[i]
                    if column_name in desired_columns:
                        row_assoc[column_name] = value
                if row_assoc:
                    response_data.append(row_assoc)

        return jsonify({
            "message": "Conteúdo do arquivo recuperado com sucesso.",
            "data": response_data
        }), 200
    
    except Exception as e:
        return jsonify({
            "message": "Erro inesperado.",
            "error": str(e)
        }), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
