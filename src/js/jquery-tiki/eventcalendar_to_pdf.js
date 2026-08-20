// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

import { jsPDF } from "common-reexported/jspdf";
import html2canvas from "html2canvas-pro";

$.fn.addEventCalendarPrint = function (buttonId, calendar) {
    let viewContainer = $(this);
    var calendarId = "#" + $(this).attr("id");
    if (!viewContainer) {
        console.warn(calendarId + " not found"); // eslint-disable-line no-console
        return;
    }
    viewContainer.append($(buttonId));
    $(buttonId).show();
    // We need to remove previoud binds to avoid $(".icon-pdf").parent() to trigger page to PDF
    $(buttonId).off("click");
    $(buttonId).on("click", function (event) {
        event.preventDefault();
        var elementToPrint = $(calendarId + " .ec");
        $("html, body").animate({ scrollTop: 0 }, 0);
        setTimeout(function () {
            html2canvas(elementToPrint[0], {
                scrollY: 0,
                scrollX: 0,
            }).then(function (canvas) {
                var calendarTitle = $(calendarId + " .ec-title")
                    .text()
                    .trim();
                var imgData = canvas.toDataURL("image/jpeg", 1.0);
                const isLandscapeFitToWidth = jqueryTiki.calendar_pdf_export_layout === "landscape_fit_to_width";
                const pageWidthMm = isLandscapeFitToWidth ? 297 : 210;
                const pageHeightMm = isLandscapeFitToWidth ? 210 : 297;

                var imgWidth = isLandscapeFitToWidth ? 267 : 180;
                var marginLeft = (pageWidthMm - imgWidth) / 2;
                var marginTop = isLandscapeFitToWidth ? 25 : 30;
                var marginRight = isLandscapeFitToWidth ? 25 : 20;
                var marginBottom = 10;

                var pageHeight = 250;
                var imgHeight = (canvas.height * imgWidth) / canvas.width;
                var heightLeft = imgHeight;

                var doc = new jsPDF(isLandscapeFitToWidth ? "l" : "p", "mm");
                doc.setFontSize(14);
                doc.text((pageWidthMm - imgWidth) / 2, 20, calendarTitle.replace(/\s+/g, " "));

                // Fit calendar to width (multi-pages)
                if (jqueryTiki.calendar_pdf_export_layout === "fit_to_width" || jqueryTiki.calendar_pdf_export_layout === "landscape_fit_to_width") {
                    const printableHeight = pageHeightMm - marginTop - marginBottom;
                    const pxPerMm = canvas.width / imgWidth; // Scale factor to convert canvas coordinates (px) into PDF units (mm)
                    const pageHeightPx = printableHeight * pxPerMm;
                    const pageNumberX = pageWidthMm - marginRight;
                    const pageNumberY = pageHeightMm - marginBottom;
                    const rows = $(calendarId + " .ec-days");
                    const containerRect = elementToPrint[0].getBoundingClientRect();
                    const rowBoundaries = [];

                    rows.each(function () {
                        const rect = this.getBoundingClientRect();
                        rowBoundaries.push({
                            top: rect.top - containerRect.top,
                            bottom: rect.bottom - containerRect.top,
                        });
                    });

                    const scaleFactor = canvas.height / elementToPrint[0].scrollHeight;

                    const scaledRows = rowBoundaries.map((r) => ({
                        top: r.top * scaleFactor,
                        bottom: r.bottom * scaleFactor,
                    }));

                    function findSafeBreak(target) {
                        let safe = target;
                        for (let i = 0; i < scaledRows.length; i++) {
                            if (scaledRows[i].top < target && scaledRows[i].bottom > target) {
                                safe = scaledRows[i].top;
                                break;
                            }
                        }
                        return safe;
                    }

                    let startY = 0;
                    let pageNumber = 1;
                    // Generating new pages
                    while (startY < canvas.height) {
                        let target = startY + pageHeightPx;
                        let endY = findSafeBreak(target);

                        if (endY <= startY) {
                            endY = Math.min(target, canvas.height);
                        }

                        endY = Math.min(endY, canvas.height);
                        const sliceHeight = Math.max(0, endY - startY);
                        if (sliceHeight <= 1) break;

                        const pageCanvas = document.createElement("canvas");
                        pageCanvas.width = canvas.width;
                        pageCanvas.height = sliceHeight;
                        const context = pageCanvas.getContext("2d");
                        context.drawImage(canvas, 0, startY, canvas.width, sliceHeight, 0, 0, canvas.width, sliceHeight);
                        const pageImage = pageCanvas.toDataURL("image/jpeg", 1.0);
                        const renderedHeight = sliceHeight / pxPerMm;

                        if (pageNumber > 1) {
                            doc.addPage();
                        }

                        doc.addImage(pageImage, "JPEG", (pageWidthMm - imgWidth) / 2, marginTop, imgWidth, renderedHeight);
                        doc.setFontSize(7);
                        doc.text("Page " + pageNumber, pageNumberX, pageNumberY);
                        pageNumber++;
                        startY = endY;
                    }
                } else {
                    // Calendar fit on one page
                    if (imgHeight > pageHeight) {
                        imgHeight = pageHeight;
                        imgWidth = (canvas.width * imgHeight) / canvas.height;
                    }
                    doc.addImage(imgData, "JPEG", (pageWidthMm - imgWidth) / 2, 30, imgWidth, heightLeft > pageHeight ? pageHeight : heightLeft);
                }
                doc.save(calendarTitle + ".pdf");
            });
        }, 200);
    });
};
