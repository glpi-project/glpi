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
                if (
                    th.querySelector('input[type="checkbox"]') ||
                    th.querySelector('div input[type="checkbox"]') ||
                    (th.querySelector('span') && th.querySelector('span').textContent.trim() === '')
                ) {
                    th.remove();
                } else if (!th.classList.length) {
                    th.classList.add('fs-6');
                    th.classList.add('text-uppercase');
                }
            });

            table.querySelectorAll('td').forEach(td => {
                if (
                    td.querySelector('input[type="checkbox"]') ||
                    td.querySelector('div input[type="checkbox"]') ||
                    td.querySelector('span.far.fa-edit')
                ) {
                    td.remove();
                }
            });
        });

        preview.querySelectorAll('div.card-footer').forEach(content => {
            content.classList.add('ps-2');
        });

        preview.querySelectorAll('.state.state_1').forEach(span => {
            span.className = 'ti ti-square';
        });

        preview.querySelectorAll('.state.state_2').forEach(span => {
            span.className = 'ti ti-square-check';
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

        const selectElements = preview.querySelectorAll('select[data-actor-type="requester"], select[data-actor-type="observer"], select[data-actor-type="assign"]');

        selectElements.forEach(selectElement => {
            const selectedValues = Array.from(selectElement.selectedOptions).map(option => option.text).join(', ');
            const spanElement = document.createElement('span');
            spanElement.textContent = selectedValues;

            const currentDiv = selectElement.parentNode;
            const previousDiv = currentDiv.previousElementSibling;

            if (previousDiv) {
                previousDiv.querySelectorAll('label').forEach(label => {
                    previousDiv.parentNode.insertBefore(label, previousDiv);
                });
                previousDiv.remove();
            }

            currentDiv.parentNode.insertBefore(spanElement, currentDiv);
            currentDiv.remove();
        });

        preview.querySelectorAll('select').forEach(select => {
            const onchangeAttr = select.getAttribute('onchange');
            if (select.getAttribute('class') === 'form-select') {
                const newSpan = document.createElement('span');
                const selectedOption = select.options[select.selectedIndex];
                newSpan.textContent = selectedOption ? selectedOption.text : '';
                const parentDiv = select.closest('div');
                if (parentDiv) {
                    parentDiv.innerHTML = '';
                    parentDiv.appendChild(newSpan);
                }
            } else if (onchangeAttr && onchangeAttr.includes('javascript:reloadTab(')) {
                const parentDiv = select.closest('div');
                if (parentDiv) {
                    parentDiv.remove();
                }
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

        window.addEventListener('beforeprint', () => {
            const sections = document.querySelectorAll('.content-card');

            function addBreakClass(sections, startIndex, accumulatedHeight) {
                if (startIndex >= sections.length) {
                    return;
                }

                const section = sections[startIndex];
                const rect = section.getBoundingClientRect();
                const sectionHeight = rect.bottom - rect.top;

                if (accumulatedHeight + sectionHeight > window.innerHeight) {
                    if (startIndex > 0) {
                        sections[startIndex].classList.add('break');
                    }
                    addBreakClass(sections, startIndex + 1, sectionHeight);
                } else {
                    addBreakClass(sections, startIndex + 1, accumulatedHeight + sectionHeight);
                }
            }

            addBreakClass(sections, 0, 0);
        });
    }
});
