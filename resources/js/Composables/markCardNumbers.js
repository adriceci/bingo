export const markCardNumbers = (card, drawnNumbers) => {
    return {
        ...card,
        numbers: card.numbers.map(number => ({
            value: number,
            marked: drawnNumbers.includes(number),
        })),
    };
};