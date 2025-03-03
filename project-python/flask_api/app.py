from flask import Flask, request, jsonify
import pandas as pd
import json 

app = Flask(__name__)

@app.route('/process_csv', methods=['POST'])
def process_csv():
    if 'file' not in request.files:
        return jsonify({"error": "Nenhum arquivo enviado"}), 400
    
    file = request.files['file']
    
    try:
        encodings = ["utf-8", "ISO-8859-1", "windows-1252"]

        file_content = None
        file_bytes = file.stream.read()

        for encoding in encodings:
            try:
                file_content = file_bytes.decode(encoding)
                break  # Se funcionar, sai do loop
            except UnicodeDecodeError:
                continue  # Se der erro, tenta o próximo encoding
        
        if file_content is None:
            return jsonify({"error": "Não foi possível decodificar o arquivo."}), 400

        file_content = file_content.replace("\r\n", "\n")
        lines = file_content.split("\n")

        # ignorar linhas inválidas
        if 'Status do Arquivo:' in lines[0]:
            lines = lines[1:] 
        
        header = lines[0].split(';')
        lines = lines[1:] 

        desired_columns = request.form.get('desired_columns')
        if desired_columns:
            desired_columns = json.loads(desired_columns) 
        else:
            return jsonify({"error": "Nenhuma coluna desejada fornecida"}), 400

        # filtros opcionais
        tckr_symb_filter = request.form.get('tckr_symb')
        rpt_dt_filter = request.form.get('rpt_dt')

        # paginação
        page = int(request.form.get('page', 1))
        per_page = int(request.form.get('per_page', 20))
        offset = (page - 1) * per_page
 
        response_data = []
        if tckr_symb_filter or rpt_dt_filter:
            for line in lines:
                row = line.split(';')
                row_assoc = {header[i]: value for i, value in enumerate(row) if header[i] in desired_columns}

                # filtragem 
                if (not tckr_symb_filter or row_assoc.get('TckrSymb') == tckr_symb_filter) and (not rpt_dt_filter or row_assoc.get('RptDt') == rpt_dt_filter):
                    response_data.append(row_assoc)
                    
            response_data = response_data[offset:offset + per_page]
        else:
            paginated_lines = lines[offset:offset + per_page]

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

@app.route('/process_excel', methods=['POST'])
def process_excel():
    if 'file' not in request.files:
        return jsonify({"error": "Nenhum arquivo enviado"}), 400

    file = request.files['file']

    try:
        df = pd.read_excel(file, sheet_name=None, header=None)
        sheet_names = df.keys()

        sheet_name = list(sheet_names)[0]
        data = df[sheet_name]

        header_row = 0
        if "Status do Arquivo:" in str(data.iloc[0].values):
            header_row = 1

        data = pd.read_excel(file, sheet_name=sheet_name, header=header_row)

        desired_columns = request.form.get('desired_columns')
        if desired_columns:
            desired_columns = json.loads(desired_columns)
        else:
            return jsonify({"error": "Nenhuma coluna desejada fornecida"}), 400

        missing_columns = [col for col in desired_columns if col not in data.columns]
        if missing_columns:
            return jsonify({"error": f"As seguintes colunas estão faltando: {', '.join(missing_columns)}"}), 400

        # filtros opcionais
        tckr_symb_filter = request.form.get('tckr_symb')
        rpt_dt_filter = request.form.get('rpt_dt')

        if rpt_dt_filter:
            try:
                rpt_dt_filter = pd.to_datetime(rpt_dt_filter, errors='raise')
            except Exception as e:
                return jsonify({"error": f"Erro ao converter rpt_dt_filter: {str(e)}"}), 400

        # paginação
        page = int(request.form.get('page', 1))
        per_page = int(request.form.get('per_page', 20))
        offset = (page - 1) * per_page

        response_data = []
        filtered_data = data[desired_columns]
        rows_added = 0

        if tckr_symb_filter or rpt_dt_filter:
            for index, row in filtered_data.iterrows():
                row_assoc = row.to_dict()

                if (not tckr_symb_filter or row_assoc.get('TckrSymb') == tckr_symb_filter) and \
                   (not rpt_dt_filter or pd.to_datetime(row_assoc.get('RptDt'), errors='coerce') == rpt_dt_filter):

                    if rows_added < per_page:
                        response_data.append(row_assoc)
                        rows_added += 1
                    else:
                        break
        else:
            paginated_data = filtered_data.iloc[offset:offset + per_page]
            response_data = paginated_data.to_dict(orient='records')

        return jsonify({
            "message": "Conteúdo do arquivo recuperado com sucesso.",
            "data": response_data
        }), 200

    except Exception as e:
        # Logar o erro
        return jsonify({
            "message": "Erro inesperado.",
            "error": str(e)
        }), 500
        
if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
