"""Agrega al resumen del job de GitHub Actions el conteo de pruebas del reporte JUnit."""
import sys
import xml.etree.ElementTree as ET

ruta = sys.argv[1] if len(sys.argv) > 1 else "reports/junit.xml"
raiz = ET.parse(ruta).getroot()
suite = raiz if raiz.tag == "testsuite" else raiz.find("testsuite")
total, fallas, errores, omitidas = (int(suite.get(k, 0)) for k in ("tests", "failures", "errors", "skipped"))

print(f"| Pruebas ejecutadas | {total} |")
print(f"| Aprobadas | {total - fallas - errores - omitidas} |")
print(f"| Fallidas / errores | {fallas} / {errores} |")
print(f"| Aserciones | {suite.get('assertions', '-')} |")
print(f"| Tiempo | {float(suite.get('time', 0)):.2f} s |")
