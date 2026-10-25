const fs=require('fs'),path=require('path'),os=require('os'),assert=require('assert/strict'),{execFileSync}=require('child_process');const root=path.resolve(__dirname,'..'),dir=fs.mkdtempSync(path.join(os.tmpdir(),'rt-supervision-xlsx-')),python='C:/Users/totyr/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/python.exe',php='C:/xampp/php/php.exe',source=path.join(dir,'source.xlsx'),output=path.join(dir,'comparison.xlsx');
try{
execFileSync(python,['-c',`import sys,openpyxl
from openpyxl.styles import PatternFill,Font
w=openpyxl.Workbook();s=w.active;s.title='Oscar David';s.append(['Nombre','EAN','Plan']);s.append(['Original','01234565','=SUM(1,2)']);s.append(['Sin EAN',None,'Mantener']);s.append(['Repetido','01234565',10]);s.append(['Sin stock','4006381333931',2]);s['B2'].number_format='@';s['B2'].font=Font(bold=True);s['B2'].fill=PatternFill('solid',fgColor='00FF00');s['C8']='Trailing total';w.create_sheet('Auxiliar')['A1']='Conservar segunda hoja';w.save(sys.argv[1])`,source]);
// Excel may pad local ZIP headers while omitting those extra fields from the central directory.
execFileSync(python,['-c',`import sys,zipfile,io,struct,pathlib
p=pathlib.Path(sys.argv[1]);original=zipfile.ZipFile(p);buffer=io.BytesIO()
with zipfile.ZipFile(buffer,'w') as target:
 for entry in original.infolist():
  content=original.read(entry)
  if entry.filename in ['[Content_Types].xml','_rels/.rels','docProps/core.xml','docProps/app.xml']:entry.extra=b'\\xfe\\xca'+struct.pack('<H',516)+b'\\0'*516
  target.writestr(entry,content)
original.close();raw=buffer.getvalue();end=raw.rfind(b'PK\\x05\\x06');start=struct.unpack_from('<I',raw,end+16)[0];position=start;central=bytearray()
while position<end:
 assert raw[position:position+4]==b'PK\\x01\\x02'
 name,extra,comment=struct.unpack_from('<3H',raw,position+28);header=bytearray(raw[position:position+46]);struct.pack_into('<H',header,30,0);central+=header+raw[position+46:position+46+name]+raw[position+46+name+extra:position+46+name+extra+comment];position+=46+name+extra+comment
footer=bytearray(raw[end:]);struct.pack_into('<I',footer,12,len(central));p.write_bytes(raw[:start]+central+footer);assert zipfile.ZipFile(p).testzip() is None
`,source]);
execFileSync(php,['-r',`require 'includes/supervision-xlsx.php';$bytes=file_get_contents($argv[1]);$preview=supervisor_parse_xlsx($bytes);if($preview['suggested']!=='B')throw new Exception('Column detection');$p=supervisor_parse_xlsx($bytes,'B');file_put_contents($argv[2],supervisor_compare_xlsx($bytes,$p['sheet_path'],$p['ean_rows'],['01234565'=>150]));`,source,output],{cwd:root});
execFileSync(python,['-c',`import sys,openpyxl,zipfile
original=openpyxl.load_workbook(sys.argv[1]);actual=openpyxl.load_workbook(sys.argv[2]);s=actual['Oscar David'];assert s['B2'].value=='01234565';assert s['B2'].font.bold;assert s['B2'].fill.fgColor.rgb=='0000FF00';assert s['C2'].value=='=SUM(1,2)';assert s['D1'].value=='Unidades';assert s['D1'].font.bold;assert s['D1'].font.color.rgb=='FFFFFFFF';assert s['D1'].fill.fgColor.rgb=='FF27187E';assert s['D2'].value==150;assert s['D3'].value is None;assert s['D4'].value==150;assert s['D5'].value==0;assert s['C8'].value=='Trailing total';assert actual['Auxiliar']['A1'].value=='Conservar segunda hoja'
a=zipfile.ZipFile(sys.argv[1]);b=zipfile.ZipFile(sys.argv[2]);assert set(a.namelist())==set(b.namelist());assert all(a.read(n)==b.read(n) for n in a.namelist() if n not in ['xl/worksheets/sheet1.xml','xl/styles.xml'])`,source,output]);
const selected=path.join(dir,'selected.xlsx'),full=path.join(dir,'full.xlsx');
execFileSync(php,['-r',`require 'includes/supervision-xlsx.php';$bytes=file_get_contents($argv[1]);$p=supervisor_parse_xlsx($bytes,'B');$columns=['name'=>'Producto','uxb'=>'UXB','expires'=>'Vencimientos (unidades por fecha)','pallets'=>'Palets (unidades por fecha)'];$detail=['01234565'=>['ean'=>'01234565','name'=>'Producto completo','uxb'=>12,'stock'=>36,'expires'=>"01/12/2026: 12 unidades\n15/12/2026: 24 unidades",'pallets'=>"Palet 00000001 · 01/12/2026: 12 unidades\nPalet 00000002 · 15/12/2026: 24 unidades"]];file_put_contents($argv[2],supervisor_compare_xlsx($bytes,$p['sheet_path'],$p['ean_rows'],['01234565'=>36],1,$columns,$detail));file_put_contents($argv[3],supervisor_write_xlsx([['EAN','Stock','Fechas'],['01234565',36,$detail['01234565']['expires']]],true));`,source,selected,full],{cwd:root});
execFileSync(python,['-c',`import sys,openpyxl,zipfile
old=openpyxl.load_workbook(sys.argv[1]);w=openpyxl.load_workbook(sys.argv[2]);s=w['Oscar David'];assert s['D1'].value=='Producto';assert s['E1'].value=='UXB';assert s['E2'].value==12;assert s['E2'].data_type=='n';assert s['F2'].value=='01/12/2026: 12 unidades\\n15/12/2026: 24 unidades';assert s['F4'].value==s['F2'].value;assert s['F2'].alignment.wrap_text;assert s.row_dimensions[2].height>=34;assert s['B2'].font.bold;assert s['B2'].fill.fgColor.rgb=='0000FF00';assert s['C2'].value=='=SUM(1,2)';assert s['F5'].value is None or s['F5'].value=='';assert w['Auxiliar']['A1'].value==old['Auxiliar']['A1'].value;assert s.max_column==7
full=openpyxl.load_workbook(sys.argv[3]).active;assert full['A2'].value=='01234565';assert full['B2'].value==36;assert full['C2'].alignment.wrap_text;assert full['C2'].value==s['F2'].value
`,source,selected,full]);
console.log('OK: real compressed openpyxl workbook imports and compares; original columns, formulas, styles, leading zero, blank rows, duplicates and other sheets preserved.');
}finally{fs.rmSync(dir,{recursive:true,force:true});}
