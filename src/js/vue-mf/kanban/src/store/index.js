import { createStore } from "vuex";
import logger from "./logger";

const debug = import.meta.env.MODE !== "production";

export default createStore({
    modules: {
        // dynamic modules
    },
    strict: debug,
    plugins: debug ? [logger] : [],
    state: {
        user: null,
        rules: [],
        accessToken: null,
        trackerId: null,
        kanbanId: null,
        swimlaneField: null,
        titleField: null,
        descriptionField: null,
        xaxisField: null,
        yaxisField: null,
        // Sample normaliazed data (Github: https://github.com/paularmstrong/normalizr)
        boards: {
            byId: {
                1: { id: 1, title: "Agile Board", rows: [1, 2, 3], imageUrl: null },
            },
            allIds: [1],
        },
        rows: {
            byId: {
                1: { id: 1, title: "UX", cells: [1, 2, 3, 4] },
                2: { id: 2, title: "Google Code-in Tasks", cells: [5, 6, 7, 8] },
                3: { id: 3, title: "Design", cells: [9, 10, 11, 12] },
            },
            allIds: [1, 2, 3],
        },
        cols: {
            byId: {
                1: { id: 1, title: "To Do", wip: 15 },
                2: { id: 2, title: "In progress", wip: 10 },
                3: { id: 3, title: "Test", wip: 100 },
                4: { id: 4, title: "Done", wip: 100 },
            },
            allIds: [1, 2, 3, 4],
        },
        cells: {
            byId: {
                1: { id: 1, title: "To do", cards: [] },
                2: { id: 2, title: "In progress", cards: [] },
                3: { id: 3, title: "Done", cards: [] },
                4: { id: 4, title: "To do", cards: [] },
                5: { id: 5, title: "In progress", cards: [] },
                6: { id: 6, title: "Done", cards: [] },
            },
            allIds: [1, 2, 3, 4, 5, 6],
        },
        cards: {
            byId: {
                1: { id: 1, column: 1, row: 1, sortOrder: 1, title: "Make start button", description: "Some card description" },
                2: { id: 2, column: 1, row: 1, sortOrder: 3, title: "Create time tracking", description: "Some card description" },
                3: { id: 3, column: 1, row: 1, sortOrder: 2, title: "Rich text formatting", description: "Some card description" },
                4: { id: 4, column: 1, row: 2, sortOrder: 3, title: "Add feature to Maps application", description: "Some card description" },
                5: { id: 5, column: 1, row: 2, sortOrder: 1, title: "Create a new activity for Sugarizer", description: "Some card description" },
                6: {
                    id: 6,
                    column: 1,
                    row: 2,
                    sortOrder: 2,
                    title: "Agora-web Display election detail during voting",
                    description: "Some card description",
                },
            },
            allIds: [1, 2, 3, 4, 5, 6],
        },
    },
    getters: {
        getUser(state) {
            return state.user;
        },
        getRules(state) {
            return state.rules;
        },
        getAccessToken(state) {
            return state.accessToken;
        },
        getTrackerId(state) {
            return state.trackerId;
        },
        getSwimlaneField(state) {
            return state.swimlaneField;
        },
        getTitleField(state) {
            return state.titleField;
        },
        getDescriptionField(state) {
            return state.descriptionField;
        },
        getXaxisField(state) {
            return state.xaxisField;
        },
        getYaxisField(state) {
            return state.yaxisField;
        },
        getCardsByCol(state) {
            return (colIndex) =>
                state.rows.allIds
                    .map((rowId) => state.rows.byId[rowId].cells[colIndex])
                    .map((cellId) => state.cells.byId[cellId].cards)
                    .flat(1)
                    .map((cardId) => state.cards.byId[cardId]);
        },
        getAllBoards(state) {
            return state.boards.allIds.map((id) => state.boards.byId[id]);
        },
        getBoard(state) {
            return (id) => state.boards.byId[id];
        },
        getAllRows(state) {
            return state.rows.allIds.map((id) => state.rows.byId[id]);
        },
        getRows(state) {
            return (ids) => ids.map((id) => state.rows.byId[id]);
        },
        getCols(state) {
            return state.cols.allIds.map((id) => state.cols.byId[id]);
        },
        getColColor(state) {
            return (id) => state.cols.byId[id].color;
        },
        getCells(state) {
            return (ids) => ids.map((id) => state.cells.byId[id]);
        },
        getCell(state) {
            return (id) => state.cells.byId[id];
        },
        getCards(state) {
            return (ids) => ids.map((id) => state.cards.byId[id]);
        },
        getCard(state) {
            return (id) => state.cards.byId[id];
        },
    },
    actions: {
        initBoard({ commit }, data) {
            commit("setBoard", data);
        },
        setUser({ commit }, data) {
            commit("setUser", data);
        },
        addRules({ commit }, data) {
            commit("addRules", data);
        },
        addBoard({ commit }, added) {
            commit("addBoard", added);
        },
        addRow({ commit }, added) {
            commit("addRow", added);
        },
        moveRowBack({ commit }, moved) {
            commit("moveRowBack", moved);
        },
        moveRowForth({ commit }, moved) {
            commit("moveRowForth", moved);
        },
        moveColumn({ commit }, moved) {
            commit("moveColumn", moved);
        },
        addCell({ commit }, added) {
            commit("addCell", added);
        },
        addNewColumn({ commit }, data) {
            commit("addNewColumn", data);
        },
        removeCell({ commit }, removed) {
            commit("removeCell", removed);
        },
        moveCard({ commit }, moved) {
            commit("moveCard", moved);
        },
        addCard({ commit }, added) {
            commit("addCard", added);
        },
        addNewCard({ commit }, data) {
            commit("addNewCard", data);
        },
        removeCard({ commit }, removed) {
            commit("removeCard", removed);
        },
        editBoardField({ commit }, { id, field, data }) {
            commit("editBoardField", { id, field, data });
        },
        editRowField({ commit }, { id, field, data }) {
            commit("editRowField", { id, field, data });
        },
        editColumnField({ commit }, { id, field, data }) {
            commit("editColumnField", { id, field, data });
        },
        editCardField({ commit }, { id, field, data }) {
            commit("editCardField", { id, field, data });
        },
    },
    mutations: {
        setUser(state, data) {
            state.user = data;
        },
        setBoard(state, data) {
            let { boards, rows, cols, cells, cards } = makeKanbanData(data);
            state.boards = boards;
            state.rows = rows;
            state.cols = cols;
            state.cells = cells;
            state.cards = cards;
            state.accessToken = data.accessToken;
            state.trackerId = data.trackerId;
            state.kanbanId = data.kanbanId;
            state.swimlaneField = data.swimlaneField;
            state.titleField = data.titleField;
            state.descriptionField = data.descriptionField;
            state.xaxisField = data.xaxisField;
            state.yaxisField = data.yaxisField;
            state.rules = data.CASLAbilityRules;
        },
        addRules(state, data) {
            state.rules = state.rules.concat(data);
        },
        addBoard(state, data) {
            // Add new board
            let newId = makeId(state.boards.allIds);
            let newRowId = makeId(state.rows.allIds);
            state.boards.allIds.push(newId);
            state.boards.byId[newId] = { id: newId, title: data.title, rows: [newRowId] };
            // Add new row
            state.rows.allIds.push(newRowId);
            state.rows.byId[newRowId] = { id: newRowId, title: "New swimlane", cells: [] };
        },
        addRow(state, data) {
            // Adds row
            let newRowId = makeId(state.rows.allIds);
            state.rows.allIds.push(newRowId);
            state.rows.byId[newRowId] = { id: newRowId, title: data.title, cells: [] };
            // Where does it push the new ID?
            // state.boards.byId[data.boardId].rows.push(newRowId)
            // Adds cells
            state.cols.allIds.forEach((id) => {
                let newId = makeId(state.cells.allIds);
                state.cells.allIds.push(newId);
                state.cells.byId[newId] = { id: newId, title: "New col", cards: [], limit: 50 };
                state.rows.byId[newRowId].cells.push(newId);
            });
        },
        moveRowBack(state, data) {
            arrayMove(state.boards.byId[data.boardId].rows, data.oldIndex, data.oldIndex - 1);
        },
        moveRowForth(state, data) {
            arrayMove(state.boards.byId[data.boardId].rows, data.oldIndex, data.oldIndex + 1);
        },
        moveColumn(state, data) {
            // arrayMove(state.rows.byId[data.rowId].cells, data.oldIndex, data.newIndex)
            state.rows.allIds.forEach((rowId) => {
                arrayMove(state.rows.byId[rowId].cells, data.oldIndex, data.newIndex);
            });
            // Sync cols
            arrayMove(state.cols.allIds, data.oldIndex, data.newIndex);
        },
        addCell(state, data) {
            state.rows.byId[data.rowId].cells.splice(data.newIndex, 0, data.element.id);
        },
        addNewColumn(state, data) {
            state.rows.allIds.forEach((rowId) => {
                let newId = makeId(state.cells.allIds);
                state.cells.allIds.push(newId);
                state.cells.byId[newId] = { id: newId, title: data.title, cards: [] };
                state.rows.byId[rowId].cells.push(newId);
            });
            let newColId = makeId(state.cols.allIds);
            state.cols.byId[newColId] = { id: newColId, title: data.title };
            state.cols.allIds.push(newColId);
        },
        removeCell(state, data) {
            state.rows.byId[data.rowId].cells.splice(data.oldIndex, 1);
        },
        moveCard(state, data) {
            arrayMove(state.cells.byId[data.cellId].cards, data.oldIndex, data.newIndex);
            setSortOrder(state, data);
        },
        addCard(state, data) {
            state.cells.byId[data.cellId].cards.splice(data.newIndex, 0, data.element.id);
            setSortOrder(state, data);
        },
        addNewCard(state, data) {
            state.cards.allIds.push(data.id);
            state.cards.byId[data.id] = { id: data.id, title: data.title, sortOrder: data.sortOrder, row: data.row, column: data.column };
            state.cells.byId[data.cellId].cards.push(data.id);
        },
        removeCard(state, data) {
            state.cells.byId[data.cellId].cards.splice(data.oldIndex, 1);
        },
        editBoardField(state, { id, field, data }) {
            state.boards.byId[id][field] = data;
        },
        editRowField(state, { id, field, data }) {
            state.rows.byId[id][field] = data;
        },
        editColumnField(state, { id, field, data }) {
            state.cols.byId[id][field] = data;
        },
        editCardField(state, { id, field, data }) {
            state.cards.byId[id][field] = data;
        },
    },
});

function makeKanbanData(data) {
    let boardsById = {};
    let boardsAllIds = [data.trackerId];
    boardsById[data.trackerId] = {
        id: data.trackerId,
        title: `Board ${data.trackerId}`,
        imageUrl: null,
        rows: [],
    };

    if (!data.rows || data.rows.length === 0) data.rows = [{ id: 0 }]; // when no data.rows or empty array
    let rowsById = data.rows;
    //console.log("CANARI", data.rows); //test2
    let rowsAllIds = Object.keys(data.rows);
    boardsById[data.trackerId].rows = rowsAllIds;

    let colsById = data.columns;
    let colsAllIds = Object.keys(data.columns);
    for (const columnId in data.columns) {
        colsById[columnId].color = "#f3f4fa";
    }

    let cellsById = {};
    let cellsAllIds = [];
    let cellId = 1;
    let rowCells = [];
    rowsAllIds.forEach((rowId) => {
        const row = rowsById[rowId];
        Object.values(colsById).forEach((col) => {
            let cardsIds = data.cards
                .filter((card) => {
                    if (row.id === 0) return card.column == col.value; // when no data.rows or empty array
                    return card.row == row.value && card.column == col.value;
                })
                .sort((a, b) => parseFloat(a.sortOrder) - parseFloat(b.sortOrder))
                .map((card) => card.id);

            cellsById[cellId] = { id: cellId, cards: cardsIds };
            cellsAllIds.push(cellId);
            rowCells.push(cellId);
            cellId++;
        });
        rowsById[rowId].cells = rowCells;
        rowCells = [];
    });

    let cardsById = {};
    let cardsAllIds = data.cards.map((card) => card.id);
    data.cards
        .map((card) => {
            card.sortOrder = parseFloat(card.sortOrder);
            return card;
        })
        .sort((a, b) => a.id - b.id)
        .forEach((card) => {
            cardsById[card.id] = card;
        });

    return {
        boards: {
            byId: boardsById,
            allIds: boardsAllIds,
        },
        rows: {
            byId: rowsById,
            allIds: rowsAllIds,
        },
        cols: {
            byId: colsById,
            allIds: colsAllIds,
        },
        cells: {
            byId: cellsById,
            allIds: cellsAllIds,
        },
        cards: {
            byId: cardsById,
            allIds: cardsAllIds,
        },
    };
}

function arrayMove(arr, fromIndex, toIndex) {
    let element = arr[fromIndex];
    arr.splice(fromIndex, 1);
    arr.splice(toIndex, 0, element);
}

function makeId(ids) {
    return ids.length ? Math.max(...ids) + 1 : 1;
}

function setSortOrder(state, data) {
    let sortOrder = 1;
    let cardIds = state.cells.byId[data.cellId].cards;
    let prevId = cardIds[data.newIndex - 1];
    let nextId = cardIds[data.newIndex + 1];
    let sum = [state.cards.byId[prevId], state.cards.byId[nextId]]
        .filter((el) => el)
        .map((el) => el.sortOrder)
        .reduce((prev, curr) => prev + curr, 0);
    if (sum > 0 && nextId) {
        sortOrder = sum / 2;
    } else if (sum >= 1) {
        sortOrder = sum + 1;
    }
    state.cards.byId[data.element.id].sortOrder = sortOrder;
}
