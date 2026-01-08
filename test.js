

// ------------------ TABLE MANAGER BUTTON ------------------
const btnTableManager = document.getElementById("btnTableManager");

btnTableManager.addEventListener("click", () => {
  fetch("tableManager-content.html")
    .then(res => res.text())
    .then(html => {
      mainContent.innerHTML = html;

      initTableManager();
      setActiveSidebar(btnTableManager);
    })
    .catch(err => console.error("Error loading table manager:", err));
});

function initTableManager() {
  const btnGenerate = document.getElementById("btnGenerateTable");
  const btnPrint = document.getElementById("btnPrintTable");
  const btnAddRow = document.getElementById("btnAddRow");
  const filterInput = document.getElementById("filterInput");
  const table = document.getElementById("dynamicTable");
  const btnAddColumn = document.getElementById("btnAddColumn");
  const btnToggleDelete = document.getElementById("btnToggleDelete");
  const btnSetTitle = document.getElementById("btnSetTitle");

  let tableData = [];
  let customColumns = [];
  let deleteMode = false;
  let customTitle = "Resident Table";

  // ===============================
  // FETCH FROM DB (READ ONLY)
  // ===============================
  async function getResidents() {
    const res = await fetch("authController.php?action=tableManager");
    return await res.json();
  }

  function getSelectedColumns() {
    return [...document.querySelectorAll(".column-option:checked")].map(cb => cb.value);
  }

  document.querySelectorAll(".column-option").forEach(cb => cb.checked = false);

  // ===============================
  // RENDER TABLE
  // ===============================
  function renderTable(data = tableData) {
    const cols = [...getSelectedColumns(), ...customColumns];

    table.querySelector("thead").innerHTML = "";
    table.querySelector("tbody").innerHTML = "";

    // Header
    const trHead = document.createElement("tr");
    trHead.innerHTML = `<th>No.</th>`;



    cols.forEach(c => {
      const th = document.createElement("th");
      th.textContent = c.charAt(0).toUpperCase() + c.slice(1);
      th.onclick = () => sortTableByColumn(c);

      if (deleteMode) {
        const del = document.createElement("span");
        del.textContent = " ×";
        del.style.color = "red";
        del.style.cursor = "pointer";
        del.onclick = e => {
          e.stopPropagation();
          if (!confirm(`Delete column "${c}"?`)) return;
          customColumns = customColumns.filter(col => col !== c);
          tableData.forEach(r => delete r[c]);
          renderTable();
        };
        th.appendChild(del);
      }

      trHead.appendChild(th);
    });

    if (deleteMode) trHead.innerHTML += `<th>×</th>`;
    table.querySelector("thead").appendChild(trHead);

    // Body
    data.forEach((r, i) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `<td>${i + 1}</td>`;

     cols.forEach(c => {
  const td = document.createElement("td");
  // ✅ YES-ONLY columns
if (["pwd", "fourPs", "voter", "seniorCitizen"].includes(c)) {
  const val = r[c];
  td.textContent = (val === 1 || val === "1" || val === true || val === "Yes") 
    ? "Yes" 
    : "";
}

  // ✅ SPECIAL HANDLING FOR SCHOOL LEVELS
  if (["college", "seniorHigh", "juniorHigh", "elementary"].includes(c)) {
    const levelMap = {
      college: "College",
      seniorHigh: "Senior High",
      juniorHigh: "Junior High",
      elementary: "Elementary"
    };

    td.textContent = (r.schoolLevels || []).includes(levelMap[c])
      ? "Yes"
      : "No";

    td.contentEditable = false; 
  }
  
  // ✅ BOOLEAN
  else if (typeof r[c] === "boolean") {
    td.textContent = r[c] ? "Yes" : "No";
    td.contentEditable = false;
  }
  // ✅ ARRAY
  else if (c === "voter" || c === "seniorCitizen") {
  td.textContent = Number(r[c]) === 1 ? "Yes" : "No";
}
  // ✅ NORMAL TEXT
  else {
    td.textContent = r[c] ?? "";
    td.contentEditable = true;
    td.onblur = () => (r[c] = td.textContent);
  }

  tr.appendChild(td);
});


      if (deleteMode) {
        const td = document.createElement("td");
        const del = document.createElement("span");
        del.textContent = "×";
        del.style.color = "red";
        del.style.cursor = "pointer";
        del.onclick = () => {
          if (!confirm("Delete row?")) return;
          tableData.splice(i, 1);
          renderTable();
        };
        td.appendChild(del);
        tr.appendChild(td);
      }

      table.querySelector("tbody").appendChild(tr);
    });
  }

  // ===============================
  // SORT
  // ===============================
  function sortTableByColumn(col) {
    tableData.sort((a, b) =>
      (a[col] ?? "").toString().localeCompare((b[col] ?? "").toString())
    );
    renderTable();
  }

  // ===============================
  // FILTER
  // ===============================
  filterInput.addEventListener("input", () => {
    const v = filterInput.value.toLowerCase();
    renderTable(
      tableData.filter(r =>
        Object.values(r).some(val =>
          String(val).toLowerCase().includes(v)
        )
      )
    );
  });

  // ===============================
  // BUTTONS
  // ===============================
  btnGenerate.onclick = async () => {
    tableData = await getResidents();
    renderTable();
  };

  btnAddRow.onclick = () => {
    const row = {};
    [...getSelectedColumns(), ...customColumns].forEach(c => (row[c] = ""));
    tableData.push(row);
    renderTable();
  };

  btnAddColumn.onclick = () => {
    const name = prompt("Column name:");
    if (!name || customColumns.includes(name)) return;
    customColumns.push(name);
    tableData.forEach(r => (r[name] = ""));
    renderTable();
  };

  btnToggleDelete.onclick = () => {
    deleteMode = !deleteMode;
    renderTable();
  };

  btnSetTitle.onclick = () => {
    const t = prompt("Enter table title:", customTitle);
    if (t) customTitle = t;
  };

  let undoStack = [];
let redoStack = [];

// Save current state sa undo stack
function saveState() {
  undoStack.push(JSON.stringify(tableData));
  redoStack = []; // Clear redo kapag may bagong action
}

// Undo
function undo() {
  if (!undoStack.length) return alert("Walang ma-undo!");
  redoStack.push(JSON.stringify(tableData));
  tableData = JSON.parse(undoStack.pop());
  renderTable();
}

// Redo
function redo() {
  if (!redoStack.length) return alert("Walang ma-redo!");
  undoStack.push(JSON.stringify(tableData));
  tableData = JSON.parse(redoStack.pop());
  renderTable();
}

document.getElementById("btnUndo").onclick = undo;
document.getElementById("btnRedo").onclick = redo;

document.getElementById("btnSaveTable").onclick = async () => {
  try {
    const res = await fetch("authController.php?action=saveTable", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(tableData)
    });
    const data = await res.json();
    if (data.success) {
      alert("Table successfully saved sa DB!");
      localStorage.setItem("tableManagerData_v1", JSON.stringify(tableData));
    } else {
      alert("Error saving table: " + data.message);
    }
  } catch (err) {
    console.error(err);
    alert("Error saving table.");
  }
};
