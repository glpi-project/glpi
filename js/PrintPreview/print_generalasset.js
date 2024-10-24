/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

$(function() {
    const preview = document.querySelector('.preview');
    if (preview) {
        preview.querySelectorAll('input').forEach(input => {
            input.setAttribute('readonly', true);
        });

        preview.querySelectorAll('select').forEach(select => {
            select.disabled = true;
        });

        const tables = preview.querySelectorAll('table, .table');

        tables.forEach(table => {
            Object.assign(table.style, {
                width: '100%',
                maxWidth: '100%',
                boxSizing: 'border-box',
                tableLayout: 'fixed',
                overflowX: 'hidden'
            });

            table.querySelectorAll('td, a, span').forEach(child => {
                Object.assign(child.style, {
                    whiteSpace: 'normal',
                    wordWrap: 'break-word',
                    overflowWrap: 'break-word'
                });
            });

            table.querySelectorAll('tfoot, tr[class*="noHover"]').forEach(element => {
                element.style.display = 'none';
            });

            table.querySelectorAll('th').forEach((th, index) => {
                if (th.querySelector('input[type="checkbox"]') || th.querySelector('div input[type="checkbox"]')) {
                    th.remove();
                } else if (th.textContent.trim() === '' || th.textContent.trim() === '\u00A0' || th.textContent.trim() === ' ') {
                    table.querySelectorAll('tr').forEach(row => {
                        if (row.children[index - 1] && (row.children[index - 1].innerHTML.trim() === '' || row.children[index - 1].textContent.trim() === '')) {
                            row.children[index - 1].remove();
                        }
                    });
                } else if (!th.classList.length) {
                    th.classList.add('fs-6');
                    th.classList.add('text-uppercase');
                }
            });

            table.querySelectorAll('td').forEach(td => {
                if (td.querySelector('input[type="checkbox"]') || td.querySelector('div input[type="checkbox"]')) {
                    td.remove();
                }
            });

            table.querySelectorAll('td.subheader').forEach((td, index) => {
                if (td.textContent.trim() === '' || td.textContent.trim() === '\u00A0') {
                    table.querySelectorAll('tr').forEach(row => {
                        if (row.children[index] && row.children[index].innerHTML.trim() === '') {
                            row.children[index].remove();
                        }
                    });
                    td.remove();
                }
            });

            table.querySelectorAll('td').forEach((td, index) => {
                if ((td.querySelector('a') && td.querySelector('a').textContent.trim() === 'Update') || td.textContent.trim() === 'Yes') {
                    table.querySelectorAll('tr').forEach(row => {
                        if (row.children[index]) {
                            row.children[index].remove();
                        }
                    });
                    td.remove();
                }
            });

            if (!table.classList.contains('netport-legend')) {
                table.querySelectorAll('tr').forEach(row => {
                    const remainingCells = Array.from(row.querySelectorAll('th, td'));
                    const remainder = 55 % remainingCells.length;
                    let baseColspan = Math.floor(55 / remainingCells.length);

                    if (table.querySelector('table.netport-legend') && row.closest('thead')) {
                        baseColspan = 55;
                    }

                    remainingCells.forEach((cell, index) => {
                        let colspan = baseColspan;
                        if (index < remainder) {
                            colspan += 1;
                        }
                        cell.setAttribute('colspan', colspan);
                    });
                });
            }
        });

        preview.querySelectorAll('label.col-form-label').forEach(label => {
            label.classList.add('col-sm-4');
            label.classList.replace('text-xxl-end', 'text-xxl-start');
            label.classList.replace('col-xxl-5', 'col-xxl-4');
        });

        preview.querySelectorAll('div.field-container').forEach(content => {
            content.classList.add('col-sm-8');
            content.classList.replace('col-xxl-7', 'col-xxl-8');
        });

        preview.querySelectorAll('div.card-footer').forEach(content => {
            content.classList.add('ps-2');
        });

        preview.querySelectorAll('span.sp-colorize-container').forEach(span => {
            span.classList.remove('sp-colorize-container');
        });

        preview.querySelectorAll('input[type="text"], input[type="search"]').forEach(input => {
            const span = document.createElement('span');
            span.textContent = input.value;
            const inlineStyle = input.getAttribute('style');
            if (inlineStyle) {
                span.setAttribute('style', inlineStyle);
                const backgroundColorMatch = inlineStyle.match(/background-color:\s*([^;]+);?/);
                if (backgroundColorMatch) {
                    const backgroundColor = backgroundColorMatch[1];

                    const colorBox = document.createElement('div');
                    colorBox.style.width = '20px';
                    colorBox.style.height = '20px';
                    colorBox.style.backgroundColor = backgroundColor;
                    colorBox.style.borderRadius = '30%';
                    colorBox.style.display = 'inline-block';
                    colorBox.style.marginRight = '5px';

                    const container = document.createElement('div');
                    container.style.display = 'flex';
                    container.style.alignItems = 'center';
                    container.appendChild(colorBox);
                    container.appendChild(span);

                    input.parentNode.replaceChild(container, input);
                    return;
                }
            }
            input.parentNode.replaceChild(span, input);
        });

        preview.querySelectorAll('span.select2-selection__rendered').forEach(renderedSpan => {
            const parentSpan = renderedSpan.closest('span.select2-selection.select2-selection--single');
            if (parentSpan) {
                const newSpan = document.createElement('span');
                newSpan.textContent = renderedSpan.getAttribute('title');
                parentSpan.parentNode.replaceChild(newSpan, parentSpan);
            }
        });

        preview.querySelectorAll('select').forEach(select => {
            const onchangeAttr = select.getAttribute('onchange');
            if (onchangeAttr && onchangeAttr.includes('javascript:reloadTab(')) {
                const parentDiv = select.closest('div');
                if (parentDiv) {
                    parentDiv.remove();
                }
            }
        });
    } else {
        const form = document.querySelector('form[name^="massaction"]');
        if (form) {
            const modal = form.querySelector('.modal-dialog');
            modal.classList.add('modal-lg');
        }
    }
});
