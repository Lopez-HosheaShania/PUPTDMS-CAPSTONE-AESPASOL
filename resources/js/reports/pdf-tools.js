let tools;

window.loadReportPdfTools = function() {
    tools ??= Promise.all([import('html2canvas-pro'), import('jspdf')])
        .then(([canvas, pdf]) => {
            window.html2canvasPro = canvas.default;
            window.jspdf = { jsPDF: pdf.jsPDF };
            return { canvasRenderer: canvas.default, jsPDF: pdf.jsPDF };
        }).catch(error => {
            tools = undefined;
            throw error;
        });
    return tools;
};
