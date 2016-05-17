 DELIMITER $$
        CREATE PROCEDURE `grab4disp`()
        BEGIN
            DECLARE charID, amt, payoutType, bankLimit, i, prodID, currBank INT;
            DECLARE cur1 CURSOR FOR
                    SELECT chardynamic.charID, charPayouts,
                        (CASE
                        WHEN prod.prodType = 0 THEN 200000000
                        WHEN prod.prodType = 1 THEN 400000000
                        WHEN prod.prodType = 2 THEN 500000000
                                END) AS bank,
                        (round(holdings.amt, 0) * 10000) AS cash, prodID, chardynamic.charBank
                    FROM chardynamic, products, holdings
                    WHERE chardynamic.prodID = gangs.gangID
                    AND products.gangID = holdings.prodID
                    AND holdings.type = 0
                    AND chardynamic.countryID = holdings.countryID
                    AND gameDB = 1;
            DECLARE CONTINUE HANDLER FOR NOT FOUND SET i = 1;
            OPEN cur1;
            SET i = 0;
            WHILE i = 0 DO
                    FETCH cur1 INTO charID, payoutType, bankLimit, amt, prodID, currBank;
                    CASE
                        WHEN payoutType = 0 THEN
                                UPDATE products
                                SET products.prodBank = products.totaBank + amt
                                WHERE products.prodID = prodID;
                        WHEN  payoutType = 1 THEN
                                IF bankLimit <= (amt + currBank)
                                THEN
                                        SET amt = (bankLimit - currBank);
                                END IF;
                                UPDATE chardynamic
                                SET charBank = (charBank + amt)
                                WHERE chardynamic.charID = charID
                                AND (charBank + amt) <= bankLimit;
                        WHEN payoutType = 2 THEN
                                UPDATE chardynamic
                                SET charCash = charCash + amt
                                WHERE chardynamic.charID = charID;
                END CASE;
            END WHILE;
            CLOSE cur1;
        END;
        $$
        DELIMITER ;